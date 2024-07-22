<template>
    <div>
        <k-headline tag="h2">Sources</k-headline>

        <k-table
            :columns="{
                source: { label: 'Source', type: 'html' },
                title: { label: 'Title / Summary', type: 'html' },
                likes: { label: 'likes', type: 'html', width: '40px', align: 'center' },
                replies: { label: 'replies', type: 'html', width: '40px', align: 'center' },
                reposts: { label: 'reposts', type: 'html', width: '40px', align: 'center' },
                mentions: { label: 'mentions', type: 'html', width: '40px', align: 'center' },
                bookmarks: { label: 'bookmarks', type: 'html', width: '40px', align: 'center' },
            }"
            :index="false"
            :rows="sourceList"
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
        sources: Object,
    },
    methods: {
        printNumberValue(value) {
            const className = value === 0 ? 'muted' : ''
            return `<span class="${className}">${value}</span>`
        },
    },
    computed: {
        sourceList() {
            const sourcesList = []
            this.sources.forEach(source => {
                const newSource = {
                    source: `<strong>${source.summary.host}</strong>`,
                    likes: this.printNumberValue(source.summary.likes),
                    replies: this.printNumberValue(source.summary.replies),
                    reposts: this.printNumberValue(source.summary.reposts),
                    mentions: this.printNumberValue(source.summary.mentions),
                    bookmarks: this.printNumberValue(source.summary.bookmarks),
                }
                sourcesList.push(newSource)

                Object.values(source.entries).forEach(entry => {
                    const newSource = {
                        source: `<a href="${entry.source}" class="source-entry"><img src="${entry.image}" width="40px" height="40px" />${entry.author}</a>`,
                        icon: ``,
                        title: `<span class="shortened-text">${entry.title}</span>`,
                        likes: this.printNumberValue(entry.likes),
                        replies: this.printNumberValue(entry.replies),
                        reposts: this.printNumberValue(entry.reposts),
                        mentions: this.printNumberValue(entry.mentions),
                        bookmarks: this.printNumberValue(entry.bookmarks),
                    }

                    sourcesList.push(newSource)
                })
            })

            return sourcesList
        },
    },
}
</script>
<style lang="scss">
.k-webmentions-view {
    .source-entry {
        display: flex;
        gap: 10px;
        align-items: center;
        color: var(--color-black);
        text-decoration: none;
    }
}
</style>
