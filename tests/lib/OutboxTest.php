<?php

use mauricerenck\IndieConnector\OptionHandlerMock;
use mauricerenck\IndieConnector\IndieConnectorDatabase;
use mauricerenck\IndieConnector\WebmentionStatus;

use Kirby\Cms\Page;
use mauricerenck\IndieConnector\OptionHandler;
use mauricerenck\IndieConnector\Outbox;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

final class OutboxTest extends TestCase
{
    private Outbox $outbox;
    private $databaseMock;

    private IndieConnectorDatabase $indieDb;
    private static OptionHandler $defaultOptions;
    private static string $dir;
    private static IndieConnectorDatabase $db;

    private static OptionHandlerMock $optionHandlerMock;
    private OptionHandlerMock $optionHandler;

    public static function setUpBeforeClass(): void
    {
        self::$optionHandlerMock = new OptionHandlerMock([]);

        // setup temp dir for sqlite test database
        self::$dir = sys_get_temp_dir() . '/indieconnector-test-' . uniqid() . '/';
        mkdir(self::$dir, 0777, true);

        self::$defaultOptions = new OptionHandlerMock([
            'sqlitePath' => self::$dir,
        ]);

        // setup database
        self::$db = new IndieConnectorDatabase(options: self::$defaultOptions);

        self::$db->query(
            'CREATE TABLE "webmentions_sent" ("id" integer,"page_uuid" text NOT NULL,"target_url" text NOT NULL,"source_url" text,"updates" int DEFAULT "1","status" text,"created_at" text,"updated_at" text, PRIMARY KEY (id))'
        );
    }

    public static function tearDownAfterClass(): void
    {
        foreach (glob(self::$dir . '*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir(self::$dir);
    }

    public function setUp(): void
    {
        parent::setUp();

        $this->optionHandler = self::$optionHandlerMock;
        $this->optionHandler->reset();

        $this->indieDb = self::$db;
        $this->indieDb->truncate(table: 'webmentions_sent');

        $this->outbox = new Outbox(options: $this->optionHandler, indieDatabase: $this->indieDb);
    }


    #[Group('outbox')]
    #[TestDox('addWebmentionTarget - should insert a pending target_url')]
    public function testAddWebmentionTarget()
    {
        $this->outbox->addWebmentionTarget(
            pageUuid: 'abc',
            pageUrl: 'https://example.com',
            targetUrl: 'https://example.com',
        );

        $results = $this->indieDb->select(
            table: 'webmentions_sent',
            fields: ['*']
        );

        $this->assertCount(1, $results);
        $this->assertContains(WebmentionStatus::Pending->value, $results->first()->toArray());
        $this->assertContains('https://example.com', $results->first()->toArray());
        $this->assertContains('abc', $results->first()->toArray());
    }

    // Test case for when no targets are registered for a page
    #[Group('outbox')]
    #[TestDox('getRegisteredWebmentionTargets - should return empty array when no webmentions exist')]
    public function testGetRegisteredWebmentionTargetsNoResults()
    {
        $results = $this->outbox->getRegisteredWebmentionTargets('nonExistentPageUuid');
        $this->assertEmpty($results);
    }

    // Test case for successful retrieval of targets (no filter)
    #[Group('outbox')]
    #[TestDox('getRegisteredWebmentionTargets - should return all target URLs')]
    public function testGetRegisteredWebmentionTargetsAllResults()
    {
        // Insert multiple targets
        $pageUuid = 'page1';
        $this->outbox->addWebmentionTarget($pageUuid, 'http://page1.com', 'https://target1.com');
        $this->outbox->addWebmentionTarget($pageUuid, 'http://page1.com', 'https://target2.com');
        $this->outbox->addWebmentionTarget($pageUuid, 'http://page1.com', 'https://target3.com');

        $results = $this->outbox->getRegisteredWebmentionTargets($pageUuid);

        $this->assertCount(3, $results);
        $this->assertContains('https://target1.com', $results);
    }

    // Test case for successful retrieval with filtering
    #[Group('outbox')]
    #[TestDox('getRegisteredWebmentionTargets - should return filtered target URLs')]
    public function testGetRegisteredWebmentionTargetsFilteredResults()
    {
        // We need to manually insert and update the status to accurately test the filter logic.
        $pageUuid = 'page_filtered';

        // 1. Insert a target
        $this->outbox->addWebmentionTarget($pageUuid, 'http://page-filtered.com', 'https://filtered_target.com');

        // 2. Manually simulate a status update for that entry (Status: Sent)
        $this->indieDb->update(
            table: 'webmentions_sent',
            values: [
                'status' => WebmentionStatus::Sent->value
            ],
            where: 'page_uuid = :page_uuid AND target_url = :target_url',
            bindings: ['page_uuid' => 'page_filtered', 'target_url' => "https://filtered_target.com"],
        );

        $results = $this->outbox->getRegisteredWebmentionTargets($pageUuid, WebmentionStatus::Sent);

        $this->assertCount(1, $results);
        $this->assertContains('https://filtered_target.com', $results);
    }

    // Test case where no targets match the status filter
    #[Group('outbox')]
    #[TestDox('getRegisteredWebmentionTargets - should return empty array when no targets match status filter')]
    public function testGetRegisteredWebmentionTargetsFilteredNoResults()
    {
        // Insert targets
        $pageUuid = 'page_filtered_none';
        $this->outbox->addWebmentionTarget($pageUuid, 'http://page-filtered-none.com', 'https://filtered_target.com');
        // Since we only insert Pending, filtering by Sent should return 0

        $results = $this->outbox->getRegisteredWebmentionTargets($pageUuid, WebmentionStatus::Sent);
        $this->assertEmpty($results);
    }


    // Test case for when no targets are registered for a page
    #[Group('outbox')]
    #[TestDox('getPendingWebmentionTargets - should return empty array when no webmentions exist')]
    public function testGetPendingWebmentionTargetsNoResults()
    {
        $results = $this->outbox->getPendingWebmentionTargets('nonExistentPageUuid');
        $this->assertEmpty($results);
    }

    // Test case for successful retrieval of pending and failed targets
    #[Group('outbox')]
    #[TestDox('getPendingWebmentionTargets - should return all target URLs matching pending/failed status and updates < 3')]
    public function testGetPendingWebmentionTargetsSuccess()
    {
        $pageUuid = 'page_pending_success';

        // Insert targets: one Pending, one Failed, both with updates < 3
        $this->outbox->addWebmentionTarget($pageUuid, 'http://page.com', 'https://pending1.com');
        $this->outbox->addWebmentionTarget($pageUuid, 'http://page.com', 'https://failed1.com');
        // Manually insert a second status (Failed). We use execute for low-level setup.
        $this->indieDb->update(
            table: 'webmentions_sent',
            values: [
                'status' => WebmentionStatus::Failed->value
            ],
            where: 'page_uuid = :page_uuid AND target_url = :target_url',
            bindings: ['page_uuid' => $pageUuid, 'target_url' => 'https://failed1.com']
        );
        $this->outbox->addWebmentionTarget($pageUuid, 'http://page.com', 'https://pending2.com');

        $results = $this->outbox->getPendingWebmentionTargets($pageUuid);

        $this->assertCount(3, $results);
        $this->assertContains('https://pending1.com', $results);
        $this->assertContains('https://failed1.com', $results);
        $this->assertContains('https://pending2.com', $results);
    }

    // Test case where targets are pending/failed, but filtered out by updates >= 3
    #[Group('outbox')]
    #[TestDox('getPendingWebmentionTargets - should return empty array when updates >= 3')]
    public function testGetPendingWebmentionTargetsUpdatesTooHigh()
    {
        // Insert a target that is Pending and has updates = 3
        $pageUuid = 'page_high_updates';
        $this->outbox->addWebmentionTarget($pageUuid, 'http://page-high-updates.com', 'https://target_high_updates.com');

        // Manually update status to 'Pending' and updates to 3
        $this->indieDb->update(
            table: 'webmentions_sent',
            values: [
                'updates' => 3
            ],
            where: 'page_uuid = :page_uuid AND target_url = :target_url',
            bindings: ['page_uuid' => $pageUuid, 'target_url' => 'https://target_high_updates.com']
        );

        $results = $this->outbox->getPendingWebmentionTargets($pageUuid);

        $this->assertEmpty($results);
    }

    // Test case where targets are low updates, but filtered out by status (e.g., Completed)
    #[Group('outbox')]
    #[TestDox('getPendingWebmentionTargets - should return empty array when status is completed')]
    public function testGetPendingWebmentionTargetsStatusWrong()
    {
        // Insert a target that is Completed (which is not Pending or Failed)
        $pageUuid = 'page_wrong_status';
        $this->outbox->addWebmentionTarget($pageUuid, 'http://page-wrong-status.com', 'https://completed_target.com');

        // Manually update status to Completed
        $this->indieDb->update(
            table: 'webmentions_sent',
            values: [
                'status' => 'Completed'
            ],
            where: 'page_uuid = :page_uuid AND target_url = :target_url',
            bindings: ['page_uuid' => $pageUuid, 'target_url' => 'https://completed_target.com']
        );

        $results = $this->outbox->getPendingWebmentionTargets($pageUuid);

        $this->assertEmpty($results);
    }

    // new tests after this


}
