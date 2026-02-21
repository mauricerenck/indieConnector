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
                    <k-icon
                        v-if="columnIndex === 'status'"
                        type="live"
                        style="color: light-dark(var(--color-purple-700),var(--color-purple-300));"
                    />
                    <k-icon
                        v-else-if="columnIndex === 'updates'"
                        type="refresh"
                        style="color: light-dark(var(--color-purple-700),var(--color-purple-300));"
                    />
                    <span v-else>{{ label }}</span>
                </span>
            </template>
            <template #options="{ row }">
                <k-options-dropdown :options="dropdownOptions(row)" />
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
                    isSource: true,
                }
                data.push(newEntry)
                this.pagination.total++

                source.entries.forEach(entry => {
                    let entryStatus = ''
                    switch (entry.status) {
                        case 'success':
                            entryStatus =
                                '<svg aria-hidden="true" data-type="check" class="k-icon" style="color: light-dark(var(--color-green-700),var(--color-green-400));"><use xlink:href="#icon-check"></use></svg>'
                            break
                        case 'blocked':
                            entryStatus =
                                '<svg xmlns="http://www.w3.org/2000/svg" style="color: light-dark(var(--color-red-700),var(--color-red-300));" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="k-icon lucide lucide-ban-icon lucide-ban"><path d="M4.929 4.929 19.07 19.071"/><circle cx="12" cy="12" r="10"/></svg>'
                            break
                        default:
                            entryStatus =
                                '<svg aria-hidden="true" data-type="cancel" class="k-icon" style="color: light-dark(var(--color-red-700),var(--color-red-300));"><use xlink:href="#icon-cancel"></use></svg>'
                            break
                    }

                    const newEntry = {
                        id: entry.id,
                        title: `<a href="${entry.url}" class="target" target="_blank">${entry.url}</a>`,
                        panelUrl: null,
                        webmentions: null,
                        url: entry.url,
                        status: entryStatus,
                        updates: entry.updates,
                        isSource: false,
                    }
                    data.push(newEntry)
                    entryCount++
                    this.pagination.total++
                })
            })
            return data.slice(this.index - 1, this.pagination.limit * this.pagination.page)
        },
    },
    methods: {
        blockEntry(row, hostOnly = false) {
            panel.api
                .post(`indieconnector/block/url`, {
                    id: row.id,
                    url: row.url,
                    direction: 'outgoing',
                    hostOnly,
                })
                .then(response => {
                    row.status =
                        '<svg xmlns="http://www.w3.org/2000/svg" style="color: light-dark(var(--color-red-700),var(--color-red-300));" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="k-icon lucide lucide-ban-icon lucide-ban"><path d="M4.929 4.929 19.07 19.071"/><circle cx="12" cy="12" r="10"/></svg>'
                })
        },

        dropdownOptions(row) {
            if (row.isSource) {
                return
            }

            return [
                {
                    label: 'Block host',
                    icon: 'protected',
                    click: () => this.blockEntry(row, true),
                },
                {
                    label: 'Open URL',
                    icon: 'url',
                    click: () => window.open(row.url, '_blank'),
                },
            ]
        },
    },
}
</script>
<style lang="css">
.k-webmentions-view {
    .target {
        margin-left: 20px;
        color: light-dark(var(--color-gray-700), var(--color-white));
    }
    .source {
        font-weight: bold;
        text-decoration: none;
    }
}
</style>
