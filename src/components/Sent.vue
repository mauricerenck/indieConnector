<template>
    <div class="wrapper">
        <k-headline size="large">Sent Webmentions</k-headline>
        <k-line-field />
        <table>
            <tr v-for="target in outbox" :key="target.id">
                <td>
                    <k-link :to="target.panelUrl" :title="target.title">{{ target.title }}</k-link>
                </td>
                <td>
                    <div>
                        <k-icon type="shape-icon-bookmark" />
                        {{ target.target }}
                    </div>
                </td>
            </tr>
        </table>
    </div>
</template>

<script>
export default {
    props: {
        outbox: Object,
    },
}
</script>
