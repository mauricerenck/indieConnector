<template>
    <div>
        <k-headline tag="h2">Responses</k-headline>

        <k-button-group class="bottom-margin">
            <k-button
                variant="filled"
                :icon="this.button.icon"
                theme="blue"
                :click="this.processQueue"
                data-type="loader"
            >
                {{ button.label }}
            </k-button>
        </k-button-group>

        <k-table
            :columns="{
                queueStatus: { label: 'Status', type: 'html', width: '160px', align: 'center' },
                urlsTotal: { label: 'URLs registered', type: 'html', align: 'center' },
                urlsMastodon: { label: 'Mastodon', type: 'html', align: 'center' },
                urlsBluesky: { label: 'Bluesky', type: 'html', align: 'center' },
                due: { label: 'Due', type: 'html', align: 'center' },
                processed: { label: 'Processed', type: 'html', align: 'center' },
                newResponses: { label: 'Responses waiting', type: 'html', align: 'center' },
            }"
            :index="false"
            :rows="queueList"
            empty="No URLs registered. Go post something!"
        >
            <template #header="{ label }">
                <span>
                    <span>{{ label }}</span>
                </span>
            </template>
        </k-table>
    </div>
</template>

<script>
export default {
    props: {
        responses: Object,
        limit: Number,
    },
    data() {
        return {
            processRunning: false,
            due: this.responses.urls.due ?? 0,
            processed: 0,
            newResponses: 0,
            button: {
                label: 'Check due URLs for responses',
                icon: 'checklist',
            },
            iteration: 0,
        }
    },
    methods: {
        printNumberValue(value) {
            const className = value === 0 ? 'muted' : ''
            return `<span class="${className}">${value}</span>`
        },

        processQueue() {
            this.processRunning = true
            this.button.label = `Fetching URLs...`
            this.button.icon = 'loader'

            this.iteration++
            panel.api
                .post(`indieconnector/responses/fill-queue`, {})
                .then(response => {
                    this.newResponses += response.responses
                    this.processed += response.urls
                    this.due -= response.urls
                })
                .then(() => {
                    if (this.due > 0 && this.processed > 0) {
                        this.processQueue()
                        return Promise.resolve()
                    }

                    this.button.label = `Done`
                    this.button.icon = 'check'

                    if (this.newResponses > 0) {
                        this.processRunning = false
                        this.processResponses()
                    } else {
                        this.iteration = 0
                        this.processRunning = false
                        this.button.label = `Check due URLs for responses`
                        this.button.icon = 'checklist'
                    }
                })
        },

        processResponses() {
            this.processRunning = true
            this.button.label = `Processing ${this.newResponses} responses...`
            this.button.icon = 'loader'

            panel.api
                .post(`indieconnector/responses/process-queue`, {})
                .then(response => {
                    this.newResponses -= response.processed
                })
                .then(() => {
                    if (this.newResponses > 0 && this.processed > 0) {
                        this.processResponses()
                        return Promise.resolve()
                    }

                    this.button.label = `Check due URLs for responses`
                    this.button.icon = 'checklist'
                    this.processRunning = false
                    panel.reload()
                })
        },
    },
    computed: {
        queueList() {
            return [
                {
                    queueStatus: this.processRunning
                        ? '<span class="status running">running</span>'
                        : '<span class="status idle">idle</span>',
                    urlsTotal: this.printNumberValue(this.responses.urls.total ?? 0),
                    urlsMastodon: this.printNumberValue(this.responses.urls.mastodon ?? 0),
                    urlsBluesky: this.printNumberValue(this.responses.urls.bluesky ?? 0),
                    due: this.printNumberValue(this.due),
                    newResponses: this.printNumberValue(this.newResponses),
                    processed: this.printNumberValue(this.processed),
                },
            ]
        },
    },
}
</script>
