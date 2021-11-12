<template>
    <div class="wrapper">
        <k-headline size="large">Targets</k-headline>
        <k-line-field />
        <table>
            <tr v-for="target in targets" :key="target.id">
                <td>
                    <k-link :to="target.panelUrl" :title="target.slug">{{ target.title }}</k-link>
                </td>
                <td>
                    <div v-bind:class="{ dimmed: target.likes === 0 }">
                        <k-icon type="shape-icon-fav" />
                        {{ target.likes }}
                    </div>
                </td>
                <td>
                    <div v-bind:class="{ dimmed: target.replies === 0 }">
                        <k-icon type="shape-icon-reply" />
                        {{ target.replies }}
                    </div>
                </td>
                <td>
                    <div v-bind:class="{ dimmed: target.reposts === 0 }">
                        <k-icon type="shape-icon-repost" />
                        {{ target.reposts }}
                    </div>
                </td>
                <td>
                    <div v-bind:class="{ dimmed: target.mentions === 0 }">
                        <k-icon type="shape-icon-mention" />
                        {{ target.mentions }}
                    </div>
                </td>
                <td>
                    <div v-bind:class="{ dimmed: target.bookmarks === 0 }">
                        <k-icon type="shape-icon-bookmark" />
                        {{ target.bookmarks }}
                    </div>
                </td>
            </tr>
        </table>
    </div>
</template>

<script>
export default {
    props: {
        targets: Object,
    },
}
</script>
