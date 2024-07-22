<template>
    <div class="wrapper">
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
        >
            <template #header="{ columnIndex, label}">
                <span>
                    <k-icon v-if="columnIndex === 'likes'" type="heart-filled" style="color: var(--color-red-700);" />
                    <k-icon v-else-if="columnIndex === 'replies'" type="chat" style="color: var(--color-yellow-700);" />
                    <k-icon
                        v-else-if="columnIndex === 'reposts'"
                        type="indie-repost"
                        style="color: var(--color-green-700);"
                    />
                    <k-icon
                        v-else-if="columnIndex === 'mentions'"
                        type="indie-mention"
                        style="color: var(--color-blue-700);"
                    />
                    <k-icon
                        v-else-if="columnIndex === 'bookmarks'"
                        type="bookmark"
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
        targets: {
            type: Array,
            default: () => [],
        },
    },
    methods: {
        printNumberValue(value) {
            const className = value === 0 ? 'muted' : ''
            return `<span class="${className}">${value}</span>`
        },
    },
    computed: {
        targetList() {
            return this.targets.map(target => {
                return {
                    title: `<a href="${target.panelUrl}" target="_blank">${target.title}</k-link>`,
                    likes: this.printNumberValue(target.likes),
                    replies: this.printNumberValue(target.replies),
                    reposts: this.printNumberValue(target.reposts),
                    mentions: this.printNumberValue(target.mentions),
                    bookmarks: this.printNumberValue(target.bookmarks),
                }
            })
        },
    },
}
</script>
