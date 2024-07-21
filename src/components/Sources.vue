<template>
    <div class="wrapper">
        <k-headline size="large">Sources</k-headline>
        <k-line-field />

        <table>
            <template v-for="(sources, index) in this.sortedSources" class="j">
                <tr class="main-source">
                    <td class="icon" colspan="3">
                        <div>
                            <k-icon
                                :type="
                                    (icon = ['twitter', 'mastodon'].includes(index)
                                        ? `indie-${index}`
                                        : 'indie-website')
                                "
                                class="source-icon"
                            />
                            {{ index }}
                        </div>
                    </td>
                    <td>
                        <div>
                            <k-icon type="heart-filled" style="color: var(--color-red-700);" />
                            {{ sources.summary.likes }}
                        </div>
                    </td>
                    <td>
                        <div>
                            <k-icon type="chat" style="color: var(--color-yellow-700);" /> {{ sources.summary.replies }}
                        </div>
                    </td>
                    <td>
                        <div>
                            <k-icon type="indie-repost" style="color: var(--color-green-700);" />
                            {{ sources.summary.reposts }}
                        </div>
                    </td>
                    <td>
                        <div>
                            <k-icon type="indie-mention" style="color: var(--color-blue-700);" />
                            {{ sources.summary.mentions }}
                        </div>
                    </td>
                    <td>
                        <div>
                            <k-icon type="bookmark" style="color: var(--color-purple-700);" />
                            {{ sources.summary.bookmarks }}
                        </div>
                    </td>
                </tr>

                <tr v-for="(source, index) in sources.entries">
                    <td>&nbsp;</td>
                    <td class="author">
                        <a :href="source.source" target="_blank">
                            <img
                                :src="source.image"
                                class="avatar"
                                v-if="source.image !== null"
                                width="30px"
                                height="30px"
                            />
                            {{ source.author }}
                        </a>
                    </td>
                    <td class="title">
                        <span class="shortened-text">{{ source.title || '&nbsp;' }}</span>
                    </td>
                    <td class="action">
                        <div>
                            <k-icon type="heart-filled" style="color: var(--color-red-400);" /> {{ source.likes }}
                        </div>
                    </td>
                    <td class="action">
                        <div><k-icon type="chat" style="color: var(--color-yellow-400);" /> {{ source.replies }}</div>
                    </td>
                    <td class="action">
                        <div>
                            <k-icon type="indie-repost" style="color: var(--color-green-400);" /> {{ source.reposts }}
                        </div>
                    </td>
                    <td class="action">
                        <div>
                            <k-icon type="indie-mention" style="color: var(--color-blue-400);" /> {{ source.mentions }}
                        </div>
                    </td>
                    <td class="action">
                        <div>
                            <k-icon type="bookmark" style="color: var(--color-purple-400);" /> {{ source.bookmarks }}
                        </div>
                    </td>
                </tr>
            </template>
        </table>
    </div>
</template>

<script>
export default {
    props: {
        sources: Object,
    },
    data() {
        return {
            sortedSources: [],
        }
    },
    created() {
        this.getSources()
    },
    methods: {
        getSources() {
            this.sortedSources = Object.entries(this.sources)
                .sort((a, b) => {
                    return a[1].sum < b[1].sum
                })
                .reduce(
                    (_sortedObj, [k, v]) => ({
                        ..._sortedObj,
                        [k]: v,
                    }),
                    {}
                )
        },
    },
}
</script>
