<template>
    <k-inside>
        <k-view class="k-webmentions-view">
            <k-header>Webmentions</k-header>
            <div class="prev-next">
                <button class="k-link k-button" v-on:click="goToPrevMonth"><k-icon type="angle-left" /></button>
                {{ month }} / {{ year }}
                <button class="k-link k-button" v-on:click="goToNextMonth"><k-icon type="angle-right" /></button>
            </div>
            <Version :version="version" />

            <DetailsByMonth :summary="summary" />
            <Targets :targets="targets" />
            <Sources :sources="sources" />
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
        version: Object,
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

    .k-icon {
        margin-right: 0.5em;
    }

    .k-link-centered {
        display: flex;
        align-content: center;
        line-height: 30px;
    }

    .avatar {
        width: 30px;
        margin-right: 0.5em;
    }

    .dimmed {
        opacity: 0.5;
    }

    table {
        width: 100%;
        border: 0;
    }

    tr:nth-child(even) {
        background-color: #f2f2f2;
    }

    th {
        text-align: left;
    }

    td {
        width: 10%;
        padding: 7px 5px;
    }

    td:first-child {
        width: 50%;
    }

    td div {
        display: flex;
        vertical-align: middle;
        align-items: center;
    }
}
</style>
