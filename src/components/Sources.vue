<template>
    <div class="wrapper">
        <k-headline size="large">Sources</k-headline>
        <k-line-field />

        <table>
            <tr v-for="source in this.sortedSources" :key="source.id">
                <td>
                    <k-link :to="source.source" :title="source.source" class="k-link-centered">
                        <img :src="source.image" class="avatar" v-if="source.image !== null" />
                        <span class="shortened-link">{{ source.source }}</span>
                    </k-link>
                </td>
                <td>
                    <div v-bind:class="{ dimmed: source.likes === 0 }">
                        <k-icon type="shape-icon-fav" />
                        {{ source.likes }}
                    </div>
                </td>
                <td>
                    <div v-bind:class="{ dimmed: source.replies === 0 }">
                        <k-icon type="shape-icon-reply" />
                        {{ source.replies }}
                    </div>
                </td>
                <td>
                    <div v-bind:class="{ dimmed: source.reposts === 0 }">
                        <k-icon type="shape-icon-repost" />
                        {{ source.reposts }}
                    </div>
                </td>
                <td>
                    <div v-bind:class="{ dimmed: source.mentions === 0 }">
                        <k-icon type="shape-icon-mention" />
                        {{ source.mentions }}
                    </div>
                </td>
                <td>
                    <div v-bind:class="{ dimmed: source.bookmarks === 0 }">
                        <k-icon type="shape-icon-bookmark" />
                        {{ source.bookmarks }}
                    </div>
                </td>
            </tr>
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
