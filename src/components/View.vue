<template>
    <k-inside>
        <k-view class="k-webmentions-view">
            <k-header>Webmentions</k-header>

            <DetailsByMonth :summary="summary" />
            <Targets :targets="targets" />
            <Sources :sources="sources" />

            <k-grid>
                <k-column width="1/4">
                    <div class="spacer">
                        <Version :version="version" />
                    </div>
                </k-column>
            </k-grid>
        </k-view>
    </k-inside>
</template>

<script>
export default {
    props: {
        summary: Object,
        targets: Array,
        sources: Array,
        version: Object,
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
