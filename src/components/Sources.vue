<template>
    <div>
        <k-headline tag="h2">Sources</k-headline>

        <k-grid style="gap: 2rem; --columns: 3">
            <k-table
                empty="No incoming webmentions yet"
                :columns="{
                    icon: { label: '', type: 'html', width: '40px', align: 'center' },
                    source: { label: 'Website', type: 'html' },
                    likes: { label: 'likes', type: 'html', width: '40px', align: 'center' },
                    replies: { label: 'replies', type: 'html', width: '40px', align: 'center' },
                    reposts: { label: 'reposts', type: 'html', width: '40px', align: 'center' },
                    mentions: { label: 'mentions', type: 'html', width: '40px', align: 'center' },
                    bookmarks: { label: 'bookmarks', type: 'html', width: '40px', align: 'center' },
                }"
                :index="false"
                :rows="sourceList"
                :pagination="{
                    page: pagination.page,
                    limit: pagination.limit,
                    total: pagination.total,
                    details: true,
                }"
                @paginate="pagination.page = $event.page"
                style="--width: 2/3"
            >
                <template #header="{ columnIndex, label}">
                    <span>
                        <k-icon
                            v-if="columnIndex === 'likes'"
                            type="heart-filled"
                            style="color: light-dark(var(--color-red-700),var(--color-red-300));"
                        />
                        <k-icon
                            v-else-if="columnIndex === 'replies'"
                            type="chat"
                            style="color: light-dark(var(--color-yellow-700),var(--color-yellow-300));"
                        />
                        <k-icon
                            v-else-if="columnIndex === 'reposts'"
                            type="indie-repost"
                            style="color: light-dark(var(--color-green-700),var(--color-green-300));"
                        />
                        <k-icon
                            v-else-if="columnIndex === 'mentions'"
                            type="indie-mention"
                            style="color: light-dark(var(--color-blue-700),var(--color-blue-300));"
                        />
                        <k-icon
                            v-else-if="columnIndex === 'bookmarks'"
                            type="bookmark"
                            style="color: light-dark(var(--color-purple-700),var(--color-purple-300));"
                        />
                        <span v-else>{{ label }}</span>
                    </span>
                </template>
                <template #options="{ row }">
                    <k-options-dropdown :options="dropdownOptions(row)" />
                </template>
            </k-table>
        </k-grid>
    </div>
</template>

<script>
import { renderIcon, printNumberValue } from '../helpers.js'
export default {
    props: {
        sources: Object,
    },
    data() {
        return {
            pagination: {
                page: 1,
                limit: 10,
                total: 0,
            },
        }
    },
    methods: {
        printNumberValue,
        renderIcon,
        blockEntry(row) {
            panel.api
                .post(`indieconnector/block/url`, {
                    id: row.id,
                    url: row.url,
                    direction: 'incoming',
                    hostOnly: true,
                })
                .then(_response => {
                    row.icon = this.renderIcon('blocked')
                })
        },
        unblockEntry(row) {
            panel.api
                .post(`indieconnector/unblock/url`, {
                    id: row.id,
                    url: row.url,
                    direction: 'incoming',
                    hostOnly: true,
                })
                .then(_response => {
                    row.icon = this.renderIcon('blocked')
                })
        },

        dropdownOptions(row) {
            if (row.isSource) {
                return
            }

            const entries = [
                {
                    label: 'Visit User',
                    icon: 'url',
                    click: () => window.open(row.url, '_blank'),
                },
            ]

            if (!row.blocked) {
                entries.push({
                    label: 'Block Host',
                    icon: 'protected',
                    click: () => this.blockEntry(row),
                })
            } else {
                entries.push({
                    label: 'Unblock Host',
                    icon: 'protected',
                    click: () => this.unblockEntry(row),
                })
            }

            return entries
        },
    },
    computed: {
        index() {
            return (this.pagination.page - 1) * this.pagination.limit + 1
        },
        sourceList() {
            const sources = []
            this.pagination.total = 0

            Object.entries(this.sources).forEach(([id, source]) => {
                let avatar = !source.source_profile_url
                    ? `<img src="${source.source_profile_url}" alt="avatar" class="avatar>`
                    : this.renderIcon('web', 'source-avatar-icon')

                let isBlocked = ''
                if (source.blocked) {
                    avatar = this.renderIcon('blocked', 'source-avatar-icon')
                    isBlocked = '(blocked)'
                }

                const newSource = {
                    icon: this.$h(avatar),
                    source: this.$h(
                        `<a href="${source.source_url}" class="source-entry">${source.source_name} ${isBlocked}</a>`
                    ),
                    likes: this.printNumberValue(source.likes),
                    replies: this.printNumberValue(source.replies),
                    reposts: this.printNumberValue(source.reposts),
                    mentions: this.printNumberValue(source.mentions),
                    bookmarks: this.printNumberValue(source.bookmarks),
                    url: source.source_url,
                    id: id,
                    blocked: source.blocked,
                }

                this.pagination.total++
                sources.push(newSource)
            })

            return sources.slice(this.index - 1, this.pagination.limit * this.pagination.page)
        },
    },
}
</script>
<style lang="css">
.k-webmentions-view {
    .source-entry {
        display: flex;
        gap: 10px;
        align-items: center;
        color: light-dark(var(--color-black), var(--color-white));
        text-decoration: none;
    }

    .source-avatar {
        width: 40px;
        height: 40px;
    }

    .source-avatar-icon {
        width: 25px;
        height: 25px;
        color: light-dark(var(--color-gray-500), var(--color-gray-250));
    }
}
</style>
