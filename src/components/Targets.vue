<template>
    <div class="wrapper">
        <k-headline size="large">Targets</k-headline>
        <k-line-field />

        <div class="k-table">
            <table>
                <tbody>
                    <tr v-for="target in targets" :key="target.id">
                        <td>
                            <k-link :to="target.panelUrl" :title="target.slug">{{ target.title }}</k-link>
                        </td>
                        <td>
                            <div>
                                <k-icon type="heart-filled" style="color: var(--color-red-700);" />
                                {{ target.likes }}
                            </div>
                        </td>
                        <td>
                            <div>
                                <k-icon type="chat" style="color: var(--color-yellow-700);" />
                                {{ target.replies }}
                            </div>
                        </td>
                        <td>
                            <div>
                                <k-icon type="indie-repost" style="color: var(--color-green-700);" />
                                {{ target.reposts }}
                            </div>
                        </td>
                        <td>
                            <div>
                                <k-icon type="indie-mention" style="color: var(--color-blue-700);" />
                                {{ target.mentions }}
                            </div>
                        </td>
                        <td>
                            <div>
                                <k-icon type="bookmark" style="color: var(--color-purple-700);" />
                                {{ target.bookmarks }}
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script>
export default {
    props: {
        targets: Object,
    },
}
</script>
