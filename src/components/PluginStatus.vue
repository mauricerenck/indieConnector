<template>
    <k-panel-inside>
        <div class="k-webmentions-status-view">
            <k-header>IndieConnector</k-header>
            <k-tabs
                tab="status"
                :tabs="[
                    { name: 'webmentions', label: 'Webmentions', link: '/webmentions' },
                    { name: 'queue', label: 'Queues', link: '/webmentions/queue', badge: itemsInQueue },
                    { name: 'status', label: 'Status', link: '/webmentions/status' },
                ]"
                theme="error"
            />

            <k-headline tag="h3">Features</k-headline>
            <k-table
                :columns="{
                    feature: { label: 'Feature', type: 'text', width: '25%' },
                    description: { label: 'Description', type: 'text' },
                    enabled: { label: 'Active', type: 'html', width: '80px', align: 'center' },
                }"
                :index="false"
                :rows="featureList"
                empty="Could not load data"
            >
                <template #header="{ label }">
                    <span>
                        <span>{{ label }}</span>
                    </span>
                </template>
            </k-table>

            <k-headline tag="h3">Receiving Webmentions</k-headline>

            <k-table
                :columns="{
                    description: { label: 'Description', type: 'text' },
                    setting: { label: 'Your setting', type: 'html' },
                    enabled: { label: 'Active', type: 'html', width: '80px', align: 'center' },
                    docs: { label: 'Docs', type: 'html', width: '80px', align: 'center' },
                }"
                :index="false"
                :rows="webmentionReceiveSettings"
                empty="Could not load data"
            >
                <template #header="{ label }">
                    <span>
                        <span>{{ label }}</span>
                    </span>
                </template>
            </k-table>

            <k-headline tag="h3">Sending Webmentions</k-headline>

            <k-table
                :columns="{
                    description: { label: 'Description', type: 'text' },
                    setting: { label: 'Your setting', type: 'html' },
                    enabled: { label: 'Active', type: 'html', width: '80px', align: 'center' },
                    docs: { label: 'Docs', type: 'html', width: '80px', align: 'center' },
                }"
                :index="false"
                :rows="webmentionSendSettings"
                empty="Could not load data"
            >
                <template #header="{ label }">
                    <span>
                        <span>{{ label }}</span>
                    </span>
                </template>
            </k-table>

            <k-headline tag="h3">Mastodon & Bluesky</k-headline>

            <k-table
                :columns="{
                    description: { label: 'Description', type: 'text' },
                    setting: { label: 'Your setting', type: 'html' },
                    enabled: { label: 'Active', type: 'html', width: '80px', align: 'center' },
                    docs: { label: 'Docs', type: 'html', width: '80px', align: 'center' },
                }"
                :index="false"
                :rows="postingSettings"
                empty="Could not load data"
            >
                <template #header="{ label }">
                    <span>
                        <span>{{ label }}</span>
                    </span>
                </template>
            </k-table>
        </div>
    </k-panel-inside>
</template>

<script>
export default {
    props: {
        features: Object,
        webmentionsSend: Object,
        webmentionsReceive: Object,
        posting: Object,
    },
    computed: {
        featureList() {
            return this.features.map(feature => {
                return {
                    feature: feature.label,
                    enabled: feature.enabled
                        ? '<div class="icon"><svg aria-hidden="true" data-type="check" class="k-icon" style="color: light-dark(var(--color-green-700),var(--color-green-400));"><use xlink:href="#icon-check"></use></svg></div>'
                        : '<div class="icon"><svg aria-hidden="true" data-type="cancel" class="k-icon" style="color: light-dark(var(--color-red-700),var(--color-red-300));"><use xlink:href="#icon-cancel"></use></svg></div>',
                    description: feature.description,
                }
            })
        },
        webmentionSendSettings() {
            return this.webmentionsSend.map(setting => {
                return {
                    description: setting.description,
                    setting: this.formatSetting(setting.setting),
                    enabled: setting.enabled
                        ? '<div class="icon"><svg aria-hidden="true" data-type="check" class="k-icon" style="color: light-dark(var(--color-green-700),var(--color-green-400));"><use xlink:href="#icon-check"></use></svg></div>'
                        : '<div class="icon"><svg aria-hidden="true" data-type="cancel" class="k-icon" style="color: light-dark(var(--color-red-700),var(--color-red-300));"><use xlink:href="#icon-cancel"></use></svg></div>',
                    docs: `<a href="${setting.docs}" class="icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="k-icon lucide lucide-external-link-icon lucide-external-link"><path d="M15 3h6v6"/><path d="M10 14 21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/></svg></a>`,
                }
            })
        },
        webmentionReceiveSettings() {
            return this.webmentionsReceive.map(setting => {
                return {
                    description: setting.description,
                    setting: this.formatSetting(setting.setting),
                    enabled: setting.enabled
                        ? '<div class="icon"><svg aria-hidden="true" data-type="check" class="k-icon" style="color: light-dark(var(--color-green-700),var(--color-green-400));"><use xlink:href="#icon-check"></use></svg></div>'
                        : '<div class="icon"><svg aria-hidden="true" data-type="cancel" class="k-icon" style="color: light-dark(var(--color-red-700),var(--color-red-300));"><use xlink:href="#icon-cancel"></use></svg></div>',
                    docs: `<a href="${setting.docs}" class="icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="k-icon lucide lucide-external-link-icon lucide-external-link"><path d="M15 3h6v6"/><path d="M10 14 21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/></svg></a>`,
                }
            })
        },
        postingSettings() {
            return this.posting.map(setting => {
                return {
                    description: setting.description,
                    setting: this.formatSetting(setting.setting),
                    enabled: setting.enabled
                        ? '<div class="icon"><svg aria-hidden="true" data-type="check" class="k-icon" style="color: light-dark(var(--color-green-700),var(--color-green-400));"><use xlink:href="#icon-check"></use></svg></div>'
                        : '<div class="icon"><svg aria-hidden="true" data-type="cancel" class="k-icon" style="color: light-dark(var(--color-red-700),var(--color-red-300));"><use xlink:href="#icon-cancel"></use></svg></div>',
                    docs: `<a href="${setting.docs}" class="icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="k-icon lucide lucide-external-link-icon lucide-external-link"><path d="M15 3h6v6"/><path d="M10 14 21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/></svg></a>`,
                }
            })
        },
    },
    methods: {
        formatSetting(setting) {
            if (typeof setting === 'object') {
                if (!setting || setting.length === 0) return ''
                return `<ul><li>${setting.join('</li><li>')}</li></ul>`
            }

            if (typeof setting === 'boolean') {
                if (!setting) return ''
                return ''
            }

            return setting
        },
    },
}
</script>

<style lang="css">
.k-webmentions-status-view {
    .wrapper {
        background: #fff;
        box-shadow: var(--box-shadow-item);
        padding: 10px 20px;
        margin-top: var(--spacing-6);
    }

    .muted {
        color: light-dark(var(--color-gray-600), var(--color-gray-300));
    }

    .icon {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    h2 {
        font-size: var(--text-3xl);
        margin: 2em 0 1em 0;
    }

    h3 {
        font-size: var(--text-xl);
        margin: 2em 0 1em 0;
    }

    .bottom-margin {
        margin-bottom: var(--spacing-6);
    }
}
</style>
