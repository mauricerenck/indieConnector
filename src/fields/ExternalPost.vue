<template>
    <div>
        <k-items
            :items="serviceItems"
            layout="list"
            :selecting="true"
            :selectable="true"
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
    data() {
        return {
            selectedServices: [],
        }
    },
    methods: {
        toggle(item) {
            const i = this.selectedServices.indexOf(item.value)
            i === -1 ? this.selectedServices.push(item.value) : this.selectedServices.splice(i, 1)

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
