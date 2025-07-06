<template>
    <div>
        <k-headline tag="h2">Targets</k-headline>

        <k-table
            :columns="{
                title: { label: 'Page', type: 'html' },
                likes: { label: 'likes', type: 'html', width: '40px', align: 'center' },
                replies: { label: 'replies', type: 'html', width: '40px', align: 'center' },
                reposts: { label: 'reposts', type: 'html', width: '40px', align: 'center' },
                mentions: { label: 'mentions', type: 'html', width: '40px', align: 'center' },
                bookmarks: { label: 'bookmarks', type: 'html', width: '40px', align: 'center' },
            }"
            :index="false"
            :rows="targetList"
            :pagination="{
                page: pagination.page,
                limit: pagination.limit,
                total: pagination.total,
                details: true,
            }"
            @paginate="pagination.page = $event.page"
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
        </k-table>
    </div>
</template>

<script>
export default {
    props: {
        targets: {
            type: Array,
            default: () => [],
        },
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
    methods: {
        printNumberValue(value) {
            const className = value === 0 ? 'muted' : ''
            return `<span class="${className}">${value}</span>`
        },
    },
    computed: {
        index() {
            return (this.pagination.page - 1) * this.pagination.limit + 1
        },
        targetList() {
            this.pagination.total = this.targets.length
            const targets = this.targets.map(target => {
                return {
                    title: `<a href="${target.panelUrl}">${target.title}</k-link>`,
                    likes: this.printNumberValue(target.likes),
                    replies: this.printNumberValue(target.replies),
                    reposts: this.printNumberValue(target.reposts),
                    mentions: this.printNumberValue(target.mentions),
                    bookmarks: this.printNumberValue(target.bookmarks),
                }
            })

            return targets.slice(this.index - 1, this.pagination.limit * this.pagination.page)
        },
    },
}
</script>
