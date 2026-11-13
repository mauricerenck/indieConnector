<?php
// Debug-Helper
// config.php:  'indieconnector.debugging.testBenchEndpoint' => 'http://localhost:4000/api/debug'
// For DDEV/Docker instead of localhost: http://host.docker.internal:4000/api/debug

namespace mauricerenck\IndieConnector;

use Kirby\Http\Remote;

final class FlowDebug
{
    public static function start(string $flow): string
    {
        if (option('mauricerenck.indieConnector.debugging.enabled', false) !== true) {
            return '';
        }

        return $flow . ':' . bin2hex(random_bytes(4));
    }

    /** status: info | ok | warn | error */
    public static function step(string $flow, string $flowId, string $step, string $status = 'info', string $message = '', mixed $data = null): void
    {
        if (option('mauricerenck.indieConnector.debugging.enabled', false) !== true) {
            return;
        }

        $url = option('mauricerenck.indieConnector.debugging.testFlowEndpoint');
        if (!$url) {
            return;
        }
        try {
            Remote::request($url, [
                'method'  => 'POST',
                'headers' => ['Content-Type: application/json'],
                'timeout' => 1,
                'data'    => json_encode([
                    'flow' => $flow,
                    'flowId' => $flow . ':' . $flowId,
                    'step' => $step,
                    'status' => $status,
                    'message' => $message,
                    'data' => $data,
                    'ts' => microtime(true) * 1000,
                ], JSON_PARTIAL_OUTPUT_ON_ERROR),
            ]);
        } catch (\Throwable $e) {
            // Debugging must not interfere with the plugin
            error_log('FlowDebug: ' . $e->getMessage());
        }
    }
}

/* example:
$id = FlowDebug::start('senden');
FlowDebug::step('senden', $id, 'Page published', 'info', $page->url());
FlowDebug::step('senden', $id, 'Found links', 'ok', count($urls) . ' Links', $urls);
FlowDebug::step('senden', $id, 'Endpoint-Discovery', 'warn', 'No Endpoint', ['target' => $target]);
*/
