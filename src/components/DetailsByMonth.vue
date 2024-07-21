<template>
    <div>
        <div class="wrapper">
            <k-headline size="large">Summary</k-headline>
            <k-line-field />
            <table>
                <tr>
                    <td>
                        {{ summary.summary }} <span v-if="summary.summary > 1">Webmentions</span
                        ><span v-else-if="summary.summary === 1">Webmention</span>
                    </td>
                    <td>
                        <div>
                            <k-icon type="heart-filled" style="color: var(--color-red-700);" />
                            {{ summary.likes }}
                        </div>
                    </td>
                    <td>
                        <div>
                            <k-icon type="chat" style="color: var(--color-yellow-700);" />
                            {{ summary.replies }}
                        </div>
                    </td>
                    <td>
                        <div>
                            <k-icon type="indie-repost" style="color: var(--color-green-700);" />
                            {{ summary.reposts }}
                        </div>
                    </td>
                    <td>
                        <div>
                            <k-icon type="indie-mention" style="color: var(--color-blue-700);" />
                            {{ summary.mentions }}
                        </div>
                    </td>
                    <td>
                        <div>
                            <k-icon type="bookmark" style="color: var(--color-purple-700);" />
                            {{ summary.bookmarks }}
                        </div>
                    </td>
                </tr>
            </table>
        </div>
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
}
</script>
