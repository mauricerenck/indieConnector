<template>
    <div>
        <k-headline tag="h2">Sources</k-headline>

        <k-grid style="gap: 2rem; --columns: 3">
            <k-table
                :columns="{
                    source: { label: ' ', width: '50px', type: 'html', align: 'center' },
                    author: { label: 'User', type: 'html' },
                    likes: { label: 'likes', type: 'html', width: '40px', align: 'center' },
                    replies: { label: 'replies', type: 'html', width: '40px', align: 'center' },
                    reposts: { label: 'reposts', type: 'html', width: '40px', align: 'center' },
                    mentions: { label: 'mentions', type: 'html', width: '40px', align: 'center' },
                    bookmarks: { label: 'bookmarks', type: 'html', width: '40px', align: 'center' },
                }"
                :index="false"
                :rows="authorList"
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
                            style="color: var(--color-red-700);"
                        />
                        <k-icon
                            v-else-if="columnIndex === 'replies'"
                            type="chat"
                            style="color: var(--color-yellow-700);"
                        />
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

            <k-table
                :columns="{
                    sourceType: { label: 'Source', type: 'text' },
                    sum: { label: 'sum', type: 'html', width: '40px', align: 'center' },
                    likes: { label: 'likes', type: 'html', width: '40px', align: 'center' },
                    replies: { label: 'replies', type: 'html', width: '40px', align: 'center' },
                    reposts: { label: 'reposts', type: 'html', width: '40px', align: 'center' },
                    mentions: { label: 'mentions', type: 'html', width: '40px', align: 'center' },
                    bookmarks: { label: 'bookmarks', type: 'html', width: '40px', align: 'center' },
                }"
                :index="false"
                :rows="sources"
            >
                <template #header="{ columnIndex, label}">
                    <span>
                        <k-icon v-if="columnIndex === 'sum'" type="plus" style="color: var(--color-black);" />
                        <k-icon
                            v-else-if="columnIndex === 'likes'"
                            type="heart-filled"
                            style="color: var(--color-red-700);"
                        />
                        <k-icon
                            v-else-if="columnIndex === 'replies'"
                            type="chat"
                            style="color: var(--color-yellow-700);"
                        />
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
        </k-grid>
    </div>
</template>

<script>
export default {
    props: {
        sources: Object,
        authors: Object,
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
        printNumberValue(value) {
            const className = value === 0 ? 'muted' : ''
            return `<span class="${className}">${value}</span>`
        },
        renderIcon(type) {
            switch (type.toLowerCase()) {
                case 'web':
                    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="source-type-icon"><path d="M12 22C6.47715 22 2 17.5228 2 12C2 6.47715 6.47715 2 12 2C17.5228 2 22 6.47715 22 12C22 17.5228 17.5228 22 12 22ZM9.71002 19.6674C8.74743 17.6259 8.15732 15.3742 8.02731 13H4.06189C4.458 16.1765 6.71639 18.7747 9.71002 19.6674ZM10.0307 13C10.1811 15.4388 10.8778 17.7297 12 19.752C13.1222 17.7297 13.8189 15.4388 13.9693 13H10.0307ZM19.9381 13H15.9727C15.8427 15.3742 15.2526 17.6259 14.29 19.6674C17.2836 18.7747 19.542 16.1765 19.9381 13ZM4.06189 11H8.02731C8.15732 8.62577 8.74743 6.37407 9.71002 4.33256C6.71639 5.22533 4.458 7.8235 4.06189 11ZM10.0307 11H13.9693C13.8189 8.56122 13.1222 6.27025 12 4.24799C10.8778 6.27025 10.1811 8.56122 10.0307 11ZM14.29 4.33256C15.2526 6.37407 15.8427 8.62577 15.9727 11H19.9381C19.542 7.8235 17.2836 5.22533 14.29 4.33256Z"></path></svg>'
                case 'mastodon':
                    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="source-type-icon"><path d="M21.2595 13.9898C20.9852 15.4006 18.8033 16.9446 16.2974 17.2439C14.9907 17.3998 13.7041 17.5431 12.3321 17.4802C10.0885 17.3774 8.31809 16.9446 8.31809 16.9446C8.31809 17.163 8.33156 17.371 8.3585 17.5655C8.65019 19.7797 10.5541 19.9124 12.3576 19.9742C14.1779 20.0365 15.7987 19.5254 15.7987 19.5254L15.8735 21.1711C15.8735 21.1711 14.6003 21.8548 12.3321 21.9805C11.0814 22.0493 9.52849 21.9491 7.71973 21.4703C3.79684 20.432 3.12219 16.2504 3.01896 12.0074C2.98749 10.7477 3.00689 9.55981 3.00689 8.56632C3.00689 4.22771 5.84955 2.95599 5.84955 2.95599C7.2829 2.29772 9.74238 2.0209 12.2993 2H12.3621C14.919 2.0209 17.3801 2.29772 18.8133 2.95599C18.8133 2.95599 21.6559 4.22771 21.6559 8.56632C21.6559 8.56632 21.6916 11.7674 21.2595 13.9898ZM18.3029 8.9029C18.3029 7.82924 18.0295 6.97604 17.4805 6.34482C16.9142 5.71359 16.1726 5.39001 15.2522 5.39001C14.187 5.39001 13.3805 5.79937 12.8473 6.61819L12.3288 7.48723L11.8104 6.61819C11.2771 5.79937 10.4706 5.39001 9.40554 5.39001C8.485 5.39001 7.74344 5.71359 7.17719 6.34482C6.62807 6.97604 6.3547 7.82924 6.3547 8.9029V14.1562H8.43597V9.05731C8.43597 7.98246 8.88822 7.4369 9.79281 7.4369C10.793 7.4369 11.2944 8.08408 11.2944 9.36376V12.1547H13.3634V9.36376C13.3634 8.08408 13.8646 7.4369 14.8648 7.4369C15.7694 7.4369 16.2216 7.98246 16.2216 9.05731V14.1562H18.3029V8.9029Z"></path></svg>'
                case 'bluesky':
                    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="source-type-icon"><path d="M12 11.3884C11.0942 9.62673 8.62833 6.34423 6.335 4.7259C4.13833 3.17506 3.30083 3.4434 2.75167 3.69256C2.11583 3.9784 2 4.95506 2 5.52839C2 6.10339 2.315 10.2367 2.52 10.9276C3.19917 13.2076 5.61417 13.9776 7.83917 13.7309C4.57917 14.2142 1.68333 15.4017 5.48083 19.6292C9.65833 23.9542 11.2058 18.7017 12 16.0392C12.7942 18.7017 13.7083 23.7651 18.4442 19.6292C22 16.0392 19.4208 14.2142 16.1608 13.7309C18.3858 13.9784 20.8008 13.2076 21.48 10.9276C21.685 10.2376 22 6.10256 22 5.52923C22 4.95423 21.8842 3.97839 21.2483 3.6909C20.6992 3.44256 19.8617 3.17423 17.665 4.72423C15.3717 6.34506 12.9058 9.62756 12 11.3884Z"></path></svg>'
                default:
                    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="source-type-icon"><path d="M12 22C6.47715 22 2 17.5228 2 12C2 6.47715 6.47715 2 12 2C17.5228 2 22 6.47715 22 12C22 17.5228 17.5228 22 12 22ZM9.71002 19.6674C8.74743 17.6259 8.15732 15.3742 8.02731 13H4.06189C4.458 16.1765 6.71639 18.7747 9.71002 19.6674ZM10.0307 13C10.1811 15.4388 10.8778 17.7297 12 19.752C13.1222 17.7297 13.8189 15.4388 13.9693 13H10.0307ZM19.9381 13H15.9727C15.8427 15.3742 15.2526 17.6259 14.29 19.6674C17.2836 18.7747 19.542 16.1765 19.9381 13ZM4.06189 11H8.02731C8.15732 8.62577 8.74743 6.37407 9.71002 4.33256C6.71639 5.22533 4.458 7.8235 4.06189 11ZM10.0307 11H13.9693C13.8189 8.56122 13.1222 6.27025 12 4.24799C10.8778 6.27025 10.1811 8.56122 10.0307 11ZM14.29 4.33256C15.2526 6.37407 15.8427 8.62577 15.9727 11H19.9381C19.542 7.8235 17.2836 5.22533 14.29 4.33256Z"></path></svg>'
            }
        },
    },
    computed: {
        index() {
            return (this.pagination.page - 1) * this.pagination.limit + 1
        },
        authorList() {
            const authors = []
            this.pagination.total = 0

            this.authors.forEach(author => {
                const newAuthor = {
                    source: this.renderIcon(author.sourceType),
                    author: `<a href="${author.source}" class="source-entry"><img src="${author.image}" width="40px" height="40px" />${author.author}</a>`,
                    likes: this.printNumberValue(author.likes),
                    replies: this.printNumberValue(author.replies),
                    reposts: this.printNumberValue(author.reposts),
                    mentions: this.printNumberValue(author.mentions),
                    bookmarks: this.printNumberValue(author.bookmarks),
                }

                authors.push(newAuthor)
                this.pagination.total++
            })

            return authors.slice(this.index - 1, this.pagination.limit * this.pagination.page)
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

    .source-type-icon {
        width: 25px;
        height: 25px;
        color: var(--color-gray-500);
    }
}
</style>
