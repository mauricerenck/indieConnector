<template>
    <k-panel-inside>
        <div class="k-webmentions-queue-view">
            <k-header>IndieConnector</k-header>
            <k-tabs
                tab="queue"
                :tabs="[
                    { name: 'webmentions', label: 'Webmentions', link: '/webmentions' },
                    { name: 'queue', label: 'Queues', link: '/webmentions/queue', badge: itemsInQueue },
                ]"
                theme="warning"
            />

            <k-info-field
                v-if="disabled"
                label="Queue disabled"
                text="The queue feature is disabled. Configure it in your config.php"
            />

            <QueueList :queuedItems="queuedItems" />

            <k-info-field
                v-if="responses.enabled"
                label="Responses enabled"
                text="Responses are enabled. Configure them in your config.php"
            />

            <ResponseList :responses="responses" />
        </div>
    </k-panel-inside>
</template>

<script>
export default {
    props: {
        disabled: Boolean,
        queuedItems: Object,
        itemsInQueue: Number,
        responses: Object,
    },

    methods: {},
}
</script>

<style lang="scss">
.k-webmentions-queue-view {
    .wrapper {
        background: #fff;
        box-shadow: var(--box-shadow-item);
        padding: 10px 20px;
        margin-top: var(--spacing-6);
    }

    .muted {
        color: var(--color-gray-600);
    }

    h2 {
        font-size: var(--text-3xl);
        margin: 2em 0 1em 0;
    }

    .bottom-margin {
        margin-bottom: var(--spacing-6);
    }

    .status {
        border: 1px solid var(--color-gray-400);
        background-color: var(--color-gray-200);

        border-radius: var(--rounded-md);
        padding: var(--spacing-1) var(--spacing-2);

        &.error {
            border: 1px solid var(--color-red-400);
            background-color: var(--color-red-200);
        }

        &.running {
            border: 1px solid var(--color-blue-400);
            background-color: var(--color-blue-200);
        }

        &.failed {
            border: 1px solid var(--color-red-600);
            background-color: var(--color-red-400);
        }

        &.success {
            border: 1px solid var(--color-green-400);
            background-color: var(--color-green-200);
        }
    }
}
</style>
