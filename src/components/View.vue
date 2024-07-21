<template>
    <k-inside>
        <k-view class="k-webmentions-view">
            <k-header>Webmentions</k-header>
            <div class="prev-next">
                <button class="k-link k-button" v-on:click="goToPrevMonth"><k-icon type="angle-left" /></button>
                {{ month }} / {{ year }}
                <button class="k-link k-button" v-on:click="goToNextMonth"><k-icon type="angle-right" /></button>
            </div>

            <DetailsByMonth :summary="summary" />
            <Sources :sources="sources" />

            <k-grid style="gap: 0.25rem; --columns: 2">
                <Sent :outbox="sent" />
                <Targets :targets="targets" />
            </k-grid>
        </k-view>
    </k-inside>
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
        targets: Array,
        sources: Array,
        sent: Array,
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
    .spacer {
        margin-top: var(--spacing-6);
    }

    .wrapper {
        background: #fff;
        box-shadow: var(--box-shadow-item);
        padding: 10px 20px;
        margin-top: var(--spacing-6);
    }

    .source-icon {
        width: 20px;
        height: 20px;
        display: inline-block;
    }

    .action-icon {
        width: 20px;
        height: 20px;
        display: inline-block;
    }

    .k-link-centered {
        display: flex;
        align-content: center;
        line-height: 30px;
    }

    .avatar {
        width: 30px;
    }

    .dimmed {
        opacity: 0.5;
    }

    table {
        width: 100%;
        border: 0;
    }

    tr:nth-child(even) {
        background-color: white;
    }

    tr:nth-child(odd) {
        background-color: var(--color-gray-100);
    }

    tr.main-source {
        background-color: var(--color-blue-200);
        font-weight: var(--font-bold);
    }

    tr:hover {
        background-color: var(--color-gray-100);
    }

    th {
        text-align: left;
    }

    td.title {
        width: 33%;
    }

    td.icon {
        width: 50px !important;

        svg {
            display: inline-block;
        }
    }

    td.author {
        width: 200px;

        a {
            display: flex;
            vertical-align: middle;
            align-items: center;
        }
    }

    td.action {
        color: var(--color-gray-600);
    }

    td div {
        display: flex;
        vertical-align: middle;
        align-items: center;
    }

    .shortened-text {
        display: block;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 80ch;
    }
}
</style>
