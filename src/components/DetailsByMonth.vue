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
                        <div v-bind:class="{ dimmed: summary.likes === 0 }">
                            <k-icon type="shape-icon-fav" />
                            {{ summary.likes }}
                        </div>
                    </td>
                    <td>
                        <div v-bind:class="{ dimmed: summary.replies === 0 }">
                            <k-icon type="shape-icon-reply" />
                            {{ summary.replies }}
                        </div>
                    </td>
                    <td>
                        <div v-bind:class="{ dimmed: summary.reposts === 0 }">
                            <k-icon type="shape-icon-repost" />
                            {{ summary.reposts }}
                        </div>
                    </td>
                    <td>
                        <div v-bind:class="{ dimmed: summary.mentions === 0 }">
                            <k-icon type="shape-icon-mention" />
                            {{ summary.mentions }}
                        </div>
                    </td>
                    <td>
                        <div v-bind:class="{ dimmed: summary.bookmarks === 0 }">
                            <k-icon type="shape-icon-bookmark" />
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
