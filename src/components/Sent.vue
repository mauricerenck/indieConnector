<template>
    <div class="wrapper">
        <k-headline size="large">Sent Webmentions</k-headline>
        <k-line-field />
        <table border="0">
            <template v-for="sources in outbox">
                <tr class="main-source">
                    <td class="icon">
                        <k-icon type="indie-website" class="source-icon" />
                    </td>
                    <td colspan="2">
                        <div>
                            <a :href="sources.page.panelUrl">
                                {{ sources.page.title }}
                            </a>
                        </div>
                    </td>

                    <td class="">
                        <div title="webmention targets">
                            <k-icon type="live" style="color: var(--color-purple-700);" />
                            {{ sources.entries.length }}
                        </div>
                    </td>
                </tr>

                <tr v-for="target in sources.entries">
                    <td class="">&nbsp;</td>
                    <td class="icon">
                        <k-icon type="check" style="color: var(--color-green-700);" v-if="target.status == 'success'" />
                        <k-icon type="cancel" style="color: var(--color-red-700);" v-else />
                    </td>

                    <td>
                        <a :href="target.url" target="_blank">
                            {{ target.url }}
                        </a>
                    </td>
                    <td class="icon" colspan="1">
                        <div title="updates">
                            <k-icon type="refresh" style="color: var(--color-purple-400);" />
                            {{ target.updates }}
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
        outbox: Array,
    },
}
</script>
