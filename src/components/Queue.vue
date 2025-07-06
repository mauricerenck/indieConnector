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

            <QueueList :queuedItems="queuedItems" v-if="!disabled" />
            <ResponseList :responses="responses" :limit="responses.limit" v-if="responses.enabled" />
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
        color: light-dark(var(--color-gray-600), var(--color-gray-300));
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
        background-color: light-dark(var(--color-gray-200), var(--color-gray-700));

        border-radius: var(--rounded-md);
        padding: var(--spacing-1) var(--spacing-2);

        &.error {
            border: 1px solid var(--color-red-400);
            background-color: light-dark(var(--color-red-600), var(--color-red-300));
        }

        &.running {
            border: 1px solid var(--color-blue-400);
            background-color: light-dark(var(--color-blue-600), var(--color-blue-300));
        }

        &.failed {
            border: 1px solid var(--color-red-600);
            background-color: light-dark(var(--color-red-600), var(--color-red-300));
        }

        &.success {
            border: 1px solid var(--color-green-400);
            background-color: light-dark(var(--color-green-600), var(--color-green-300));
        }
    }
}
</style>
