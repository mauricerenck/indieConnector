<template>
    <div>
        <k-headline tag="h2">Summary</k-headline>

        <k-table
            :columns="{
                summary: { label: 'Webmentions received', type: 'html', align: 'center' },
                likes: { label: 'likes', type: 'html', align: 'center' },
                replies: { label: 'replies', type: 'html', align: 'center' },
                reposts: { label: 'reposts', type: 'html', align: 'center' },
                mentions: { label: 'mentions', type: 'html', align: 'center' },
                bookmarks: { label: 'bookmarks', type: 'html', align: 'center' },
            }"
            :index="false"
            :rows="[targetList]"
        >
            <template #header="{ columnIndex, label}">
                <div class="center-icon">
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
                </div>
            </template>
        </k-table>
    </div>
</template>

<script>
export default {
    props: {
        summary: {
            summary: Number,
            likes: Number,
            replies: Number,
            reposts: Number,
            mentions: Number,
            bookmarks: Number,
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
            return {
                summary: this.printNumberValue(this.summary.summary),
                likes: this.printNumberValue(this.summary.likes),
                replies: this.printNumberValue(this.summary.replies),
                reposts: this.printNumberValue(this.summary.reposts),
                mentions: this.printNumberValue(this.summary.mentions),
                bookmarks: this.printNumberValue(this.summary.bookmarks),
            }
        },
    },
}
</script>
