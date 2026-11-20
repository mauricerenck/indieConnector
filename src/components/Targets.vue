<template>
    <div>
        <k-headline tag="h2">Sent Webmentions</k-headline>

        <k-table
            empty="No incoming webmentions yet"
            :columns="{
                icon: { label: '', type: 'html', width: '40px', align: 'center' },
                source: { label: 'Source', type: 'html' },
                target: { label: 'Target', type: 'html' },
                updated_at: { label: 'Last update', align: 'right' },
                status: { label: 'bookmarks', type: 'html', width: '40px', align: 'center' },
            }"
            :index="false"
            :rows="targetList"
            :pagination="{
                page: pagination.page,
                limit: pagination.limit,
                total: targetEntries.length,
                details: true,
            }"
            @paginate="pagination.page = $event.page"
            style="--width: 2/3"
        >
            <template #header="{ columnIndex, label}">
                <span>
                    <k-icon v-if="columnIndex === 'status'" type="info" />
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
        targets: Object,
    },
    data() {
        return {
            pagination: {
                page: 1,
                limit: 10,
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
                    url: row.target_url,
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
                    url: row.target_url,
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
                    click: () => window.open(row.source_url, '_blank'),
                },
                {
                    label: 'Open in panel',
                    icon: 'kirby',
                    link: row.source_panel_url,
                },
                '-',
                {
                    label: 'Visit Source Page',
                    icon: 'open',
                    link: row.target_url,
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
        targetList() {
            return this.targetEntries.slice(this.index - 1, this.pagination.limit * this.pagination.page)
        },
        targetEntries() {
            const targets = []

            Object.entries(this.targets).forEach(([id, target]) => {
                let avatar = !target.target_avatar
                    ? `<img src="${target.target_avatar}" alt="avatar" class="avatar>`
                    : this.renderIcon('web', 'source-avatar-icon')

                let isBlocked = ''
                if (target.blocked) {
                    avatar = this.renderIcon('blocked', 'source-avatar-icon')
                    isBlocked = '(blocked)'
                }

                let statusIcon = ''
                switch (target.status) {
                    case 'PENDING':
                        statusIcon = this.renderIcon('pending')
                        break
                    case 'FAILED':
                        statusIcon = this.renderIcon('failed')
                        break
                    case 'SENT':
                        statusIcon = this.renderIcon('success')
                        break
                    default:
                        statusIcon = this.renderIcon('unknown')
                        break
                }

                const newTarget = {
                    icon: this.$h(avatar),
                    target: this.$h(
                        `<a href="${target.target_url}" class="source-entry">${target.target_name} <em>(${target.host})</em> ${isBlocked}</a>`
                    ),
                    source: this.$h(
                        `<a href="${target.source_panel_url}" class="source-entry">${target.source_name}</a>`
                    ),
                    updated_at: this.$library.dayjs(target.updated_at).format('YYYY-MM-DD HH:mm'),
                    source_url: target.source_url,
                    source_panel_url: target.source_panel_url,
                    target_url: target.target_url,
                    id: id,
                    blocked: target.blocked,
                    status: statusIcon,
                }

                targets.push(newTarget)
            })

            return targets
        },
    },
}
</script>
