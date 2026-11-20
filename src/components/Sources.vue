<template>
    <div>
        <k-headline tag="h2">Received Webmentions</k-headline>

        <k-table
            empty="No incoming webmentions yet"
            :columns="{
                icon: { label: '', type: 'html', width: '40px', align: 'center' },
                source: { label: 'Source', type: 'html' },
                target: { label: 'Target', type: 'html' },
                updated_at: { label: 'Last update', align: 'right' },
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
        blockEntry(row, hostOnly) {
            panel.api
                .post(`indieconnector/block/url`, {
                    id: row.id,
                    url: row.source_url,
                    direction: 'incoming',
                    hostOnly: hostOnly,
                })
                .then(_response => {
                    row.icon = this.renderIcon('blocked')
                })
        },
        unblockEntry(row, hostOnly) {
            panel.api
                .post(`indieconnector/unblock/url`, {
                    id: row.id,
                    url: row.source_url,
                    direction: 'incoming',
                    hostOnly: hostOnly,
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
                    label: 'Open Page',
                    icon: 'open',
                    click: () => window.open(row.target_url, '_blank'),
                },
                {
                    label: 'Open in panel',
                    icon: 'kirby',
                    link: row.target_panel_url,
                },
                '-',
                {
                    label: 'Visit Source Profile',
                    icon: 'user',
                    link: row.source_profile_url,
                    target: '_blank',
                },
                {
                    label: 'Visit Source Page',
                    icon: 'open',
                    link: row.source_url,
                    target: '_blank',
                },
                '-',
            ]

            if (!row.blocked) {
                entries.push({
                    label: 'Block Host',
                    icon: 'protected',
                    theme: 'red',
                    click: () => this.blockEntry(row, true),
                })
                entries.push({
                    label: 'Block URL',
                    icon: 'protected',
                    theme: 'red',
                    click: () => this.blockEntry(row, false),
                })
            } else {
                entries.push({
                    label: 'Unblock Host',
                    icon: 'protected',
                    theme: 'green',
                    click: () => this.unblockEntry(row, true),
                })
                entries.push({
                    label: 'Unblock URL',
                    icon: 'protected',
                    theme: 'green',
                    click: () => this.unblockEntry(row, false),
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
                let avatar = !source.source_avatar
                    ? `<img src="${source.source_avatar}" alt="avatar" class="avatar>`
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
                    target: this.$h(
                        `<a href="${source.target_panel_url}" class="source-entry">${source.target_name}</a>`
                    ),

                    updated_at: this.$library.dayjs(source.updated_at).format('YYYY-MM-DD HH:mm'),
                    likes: this.printNumberValue(source.likes),
                    replies: this.printNumberValue(source.replies),
                    reposts: this.printNumberValue(source.reposts),
                    mentions: this.printNumberValue(source.mentions),
                    bookmarks: this.printNumberValue(source.bookmarks),
                    source_id: source.source_id,
                    source_url: source.source_url,
                    source_profile_url: source.source_profile_url,
                    target_url: source.target_url,
                    target_panel_url: source.target_panel_url,
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
