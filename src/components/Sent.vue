<template>
    <div>
        <k-headline tag="h2">Sent Webmentions</k-headline>

        <k-table
            :columns="{
                title: { label: 'Page / Target', type: 'html' },
                updates: { label: 'Updates', type: 'text', width: '40px', align: 'center' },
                status: { label: 'Status', type: 'html', width: '40px', align: 'center' },
            }"
            :index="false"
            :rows="sentList"
            :pagination="{
                page: pagination.page,
                limit: pagination.limit,
                total: pagination.total,
                details: true,
            }"
            @paginate="pagination.page = $event.page"
        >
            <template #header="{ columnIndex, label}">
                <span :title="label">
                    <k-icon v-if="columnIndex === 'status'" type="live" style="color: var(--color-purple-700);" />
                    <k-icon
                        v-else-if="columnIndex === 'updates'"
                        type="refresh"
                        style="color: var(--color-purple-700);"
                    />
                    <span v-else>{{ label }}</span>
                </span>
            </template>
        </k-table>
    </div>
</template>

<script>
export default {
    props: {
        outbox: Array,
    },
    data() {
        return {
            pagination: {
                page: 1,
                limit: 20,
                total: 0,
            },
        }
    },
    computed: {
        index() {
            return (this.pagination.page - 1) * this.pagination.limit + 1
        },
        sentList() {
            const data = []
            this.pagination.total = 0

            this.outbox.forEach(source => {
                var entryCount = 0

                const newEntry = {
                    title: `<a href="${source.page.panelUrl}" class="source group-label" >${source.page.title}</a>`,
                    panelUrl: source.page.panelUrl,
                    webmentions: source.entries.length,
                    url: null,
                    status: null,
                    updates: null,
                }
                data.push(newEntry)
                this.pagination.total++

                source.entries.forEach(entry => {
                    const newEntry = {
                        title: `<a href="${entry.url}" class="target" target="_blank">${entry.url}</a>`,
                        panelUrl: null,
                        webmentions: null,
                        url: entry.url,
                        status:
                            entry.status === 'success'
                                ? '<svg aria-hidden="true" data-type="check" class="k-icon" style="color: var(--color-green-700);"><use xlink:href="#icon-check"></use></svg>'
                                : '<svg aria-hidden="true" data-type="cancel" class="k-icon" style="color: var(--color-red-700);"><use xlink:href="#icon-cancel"></use></svg>',
                        updates: entry.updates,
                    }
                    data.push(newEntry)
                    entryCount++
                    this.pagination.total++
                })
            })
            return data.slice(this.index - 1, this.pagination.limit * this.pagination.page)
        },
    },
}
</script>
<style lang="scss">
.k-webmentions-view {
    .target {
        margin-left: 20px;
        color: var(--color-gray-700);
    }
    .source {
        font-weight: bold;
        text-decoration: none;
    }
}
</style>
