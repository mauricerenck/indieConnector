<template>
    <k-panel-inside>
        <div class="k-webmentions-view">
            <k-header>IndieConnector</k-header>
            <k-tabs
                tab="webmentions"
                :tabs="[
                    { name: 'webmentions', label: 'Webmentions', link: '/webmentions' },
                    { name: 'queue', label: 'Queues', link: '/webmentions/queue', badge: itemsInQueue },
                    { name: 'status', label: 'Status', link: '/webmentions/status' },
                ]"
                theme="warning"
            />

            <div class="prev-next">
                <button class="k-link k-button" v-on:click="goToPrevMonth"><k-icon type="angle-left" /></button>
                {{ month }} / {{ year }}
                <button class="k-link k-button" v-on:click="goToNextMonth"><k-icon type="angle-right" /></button>
            </div>

            <!-- <DetailsByMonth :summary="summary" /> -->
            <Sources :sources="sources" />
            <Targets :targets="targets" />

            <k-grid style="gap: 2rem; --columns: 2">
                <!-- <Sent :outbox="sent" /> -->
            </k-grid>
        </div>
    </k-panel-inside>
</template>

<script>
export default {
    props: {
        year: String,
        month: String,

        summary: Object,
        targets: Object,
        sources: Object,
        authors: Array,
        sent: Array,
        itemsInQueue: Number,
    },
    data() {
        return {
            pagination: {
                page: 1,
                limit: 10,
                total: 0,
            },
            processIndex: 0,
            processRunning: false,
        }
    },
    methods: {
        goToPrevMonth() {
            const panelPath = window.location.pathname.split('indieconnector')
            const newDate = this.$library.dayjs(`${this.year}-${this.month}-01`).subtract(1, 'month')
            window.location.pathname = `${panelPath[0]}indieconnector/${newDate.format('YYYY/MM')}`
        },
        goToNextMonth() {
            const panelPath = window.location.pathname.split('indieconnector')
            const newDate = this.$library.dayjs(`${this.year}-${this.month}-01`).add(1, 'month')
            window.location.pathname = `${panelPath[0]}indieconnector/${newDate.format('YYYY/MM')}`
        },
    },
}
</script>

<style lang="css">
.k-webmentions-view {
    .wrapper {
        background: #fff;
        box-shadow: var(--box-shadow-item);
        padding: 10px 20px;
        margin-top: var(--spacing-6);
    }

    .muted {
        color: light-dark(var(--color-gray-600), var(--color-gray-700));
    }

    .shortened-text {
        display: block;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 80ch;
    }

    h2 {
        font-size: var(--text-3xl);
        margin: 2em 0 1em 0;
    }

    .center-icon {
        display: flex;
        justify-content: center;

        svg {
            display: inline-block;
        }
    }

    .group-label {
        background-color: light-dark(var(--color-blue-700), var(--color-blue-800));
        color: var(--color-white);
        padding: 2px 5px;
        border-radius: var(--rounded-md);
    }
}
</style>
