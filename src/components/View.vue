<template>
    <k-panel-inside>
        <div class="k-webmentions-view">
            <k-header>IndieConnector</k-header>
            <k-tabs
                tab="webmentions"
                :tabs="[
                    { name: 'webmentions', label: 'Webmentions', link: '/webmentions' },
                    { name: 'queue', label: 'Queues', link: '/webmentions/queue', badge: itemsInQueue },
                ]"
                theme="warning"
            />

            <div class="prev-next">
                <button class="k-link k-button" v-on:click="goToPrevMonth"><k-icon type="angle-left" /></button>
                {{ month }} / {{ year }}
                <button class="k-link k-button" v-on:click="goToNextMonth"><k-icon type="angle-right" /></button>
            </div>

            <DetailsByMonth :summary="summary" />
            <Sources :sources="sources" :authors="authors" />

            <k-grid style="gap: 2rem; --columns: 2">
                <Sent :outbox="sent" />
                <Targets :targets="targets" />
            </k-grid>
        </div>
    </k-panel-inside>
</template>

<script>
export default {
    props: {
        year: Number,
        month: Number,
        nextYear: Number,
        nextMonth: Number,
        prevYear: Number,
        prevMonth: Number,
        summary: Object,
        targets: { type: Array, default: [] },
        sources: Array,
        authors: Array,
        sent: Array,
        itemsInQueue: Number,
    },

    methods: {
        goToPrevMonth() {
            const panelPath = window.location.pathname.split('webmentions')
            window.location.pathname = `${panelPath[0]}webmentions/${this.prevYear}/${this.prevMonth}`
        },
        goToNextMonth() {
            const panelPath = window.location.pathname.split('webmentions')
            window.location.pathname = `${panelPath[0]}webmentions/${this.nextYear}/${this.nextMonth}`
        },
    },
}
</script>

<style lang="scss">
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
