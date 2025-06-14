<template>
    <div>
        <k-headline tag="h2">Queue</k-headline>

        <k-button-group class="bottom-margin">
            <k-button
                variant="filled"
                :icon="processRunning ? 'loader' : 'refresh'"
                :theme="this.queueList.length === 0 ? 'gray' : 'blue'"
                :disabled="this.queueList.length === 0"
                :click="this.processQueue"
                data-type="loader"
                >Process queue</k-button
            >
            <k-button
                variant="filled"
                icon="trash"
                :theme="!hasFailed ? 'gray' : 'red'"
                :disabled="!hasFailed"
                :click="
                    () => {
                        this.cleanQueue('failed')
                    }
                "
                >Delete failed</k-button
            >
            <k-button
                variant="filled"
                icon="trash"
                :theme="!hasErrors ? 'gray' : 'orange'"
                :disabled="!hasErrors"
                :click="
                    () => {
                        this.cleanQueue('error')
                    }
                "
                >Delete errors</k-button
            >
            <k-button
                variant="filled"
                icon="trash"
                :theme="queueList.length === 0 ? 'gray' : 'orange'"
                :disabled="queueList.length === 0"
                :click="
                    () => {
                        this.cleanQueue('queued')
                    }
                "
            >
                Empty queue
            </k-button>
        </k-button-group>

        <k-table
            :columns="{
                queueStatus: { label: 'Status', type: 'html', width: '160px', align: 'center' },
                source: { label: 'Source', type: 'html' },
                target: { label: 'Target', type: 'html' },
                message: { label: 'Message', type: 'text' },
                retries: { label: 'Retries', type: 'text', width: '80px', align: 'center' },
            }"
            :index="false"
            :rows="queueList"
            :pagination="{
                page: pagination.page,
                limit: pagination.limit,
                total: pagination.total,
                details: true,
            }"
            @paginate="pagination.page = $event.page"
            empty="The queue is empty"
        >
            <template #header="{ label }">
                <span>
                    <span>{{ label }}</span>
                </span>
            </template>
            <template #options="{ row }">
                <k-options-dropdown :options="dropdownOptions(row)" />
            </template>
        </k-table>
    </div>
</template>

<script>
export default {
    props: {
        queuedItems: Array,
    },
    data() {
        return {
            pagination: {
                page: 1,
                limit: 10,
                total: 0,
            },
            processIndex: 0,
            processRunning: false,
        }
    },
    methods: {
        printNumberValue(value) {
            const className = value === 0 ? 'muted' : ''
            return `<span class="${className}">${value}</span>`
        },

        processQueueItem(id) {
            panel.api.post(`indieconnector/queue/processItem/${id}`).then(response => {
                const affectedItem = this.queuedItems.find(item => item.id === id)
                affectedItem.queueStatus = response.queue_Status
                affectedItem.retries = response.retries
                affectedItem.processLog = response.process_log
            })
        },

        processQueue() {
            const limit = 2

            if (this.processIndex >= this.queuedItems.length) {
                this.processIndex = 0
                this.processRunning = false

                setTimeout(() => {
                    panel.reload()
                }, 500)

                return
            }

            this.processRunning = true

            const itemsWithStatus =
                this.processIndex > 0
                    ? this.queuedItems.filter(
                          item =>
                              item.queueStatus !== 'failed' &&
                              item.queueStatus !== 'error' &&
                              item.queueStatus !== 'success'
                      )
                    : this.queuedItems.filter(item => item.queue_status !== 'failed' && item.queue_status !== 'success')
            const processList = itemsWithStatus.slice(0, limit)
            const processIds = processList.map(item => item.id)

            processList.forEach(item => {
                item.queueStatus = 'running'
            })

            this.processIndex += limit

            panel.api
                .post(`indieconnector/queue/process`, processIds)
                .then(response => {
                    response.forEach(responseItem => {
                        const affectedItem = this.queuedItems.find(item => item.id === responseItem.id)
                        affectedItem.queueStatus = responseItem.queue_status
                        affectedItem.retries = responseItem.retries
                        affectedItem.processLog = responseItem.process_log
                    })
                })
                .then(() => {
                    this.processQueue()
                })
        },

        deleteQueueItem(id) {
            panel.dialog.open(`queue/delete/${id}`)
        },

        cleanQueue(status) {
            panel.dialog.open(`queue/clean/${status}`)
        },

        dropdownOptions(row) {
            return [
                {
                    label: 'Process',
                    icon: 'refresh',
                    click: () => this.processQueueItem(row.id),
                },
                {
                    label: 'Delete',
                    icon: 'trash',
                    click: () => this.deleteQueueItem(row.id),
                },
            ]
        },
    },
    computed: {
        index() {
            return (this.pagination.page - 1) * this.pagination.limit + 1
        },

        hasErrors() {
            return this.queuedItems.filter(item => item.queue_status === 'error').length > 0
        },

        hasFailed() {
            return this.queuedItems.filter(item => item.queue_status === 'failed').length > 0
        },

        queueList() {
            const itemList = []
            this.pagination.total = 0

            this.queuedItems.forEach(queueEntry => {
                const sourceLabel = queueEntry.source_service ? queueEntry.source_service.name : queueEntry.source_url
                const newQueueItem = {
                    id: queueEntry.id,
                    source: `<a href="${queueEntry.source_url}?panelPreview=true" target="_blank">${sourceLabel}</a>`,
                    target: `<a href="${queueEntry.target_url}" target="_blank">${queueEntry.target_url}</a>`,
                    queueStatus: `<span class="status ${queueEntry.queue_status}">${queueEntry.queue_status}</span>`,
                    message: queueEntry.process_log,
                    retries: queueEntry.retries ?? 0,
                }

                itemList.push(newQueueItem)
                this.pagination.total++
            })

            return itemList.slice(this.index - 1, this.pagination.limit * this.pagination.page)
        },
    },
}
</script>
