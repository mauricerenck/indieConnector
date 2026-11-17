<template>
    <div>
        <k-items
            :items="items"
            layout="list"
            :selecting="true"
            :selectable="true"
            :selected="selectedServices"
            :link="true"
            @select="toggle"
        />
    </div>
</template>

<script>
export default {
    props: {
        serviceItems: Array,
        value: {
            type: Array,
            default: () => [],
        },
    },
    emits: ['input'],
    data() {
        return {
            selectedServices: [],
        }
    },
    computed: {
        items() {
            return this.serviceItems.map(item => ({ ...item, id: item.value }))
        },
    },
    methods: {
        toggle(selected) {
            this.selectedServices = selected

            // 2. Jetzt weiß das Panel, dass sich etwas geändert hat
            this.$emit('input', this.selectedServices)
        },
    },
    // Externe Änderungen (z. B. Reset) nachziehen
    watch: {
        value(changedValue) {
            this.selectedServices = [...changedValue]
        },
    },
}
</script>

<style>
/* optional scoped styles for the component */
</style>
