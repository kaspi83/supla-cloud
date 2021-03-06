<template>
    <loading-cover :loading="!auditEntries">
        <div class="list-group"
            v-if="auditEntries">
            <div :class="'list-group-item list-group-item-' + (entry.event.name == 'DIRECT_LINK_EXECUTION' ? 'success' : 'danger')"
                v-for="entry in auditEntries">
                <div v-if="entry.event.name == 'DIRECT_LINK_EXECUTION'">
                    {{ $t('Executed action')}}
                    <strong>{{ functionLabel(entry.textParam) }}</strong>
                </div>
                <div v-else>
                    {{ entry.textParam }}
                </div>
                <div class="text-muted small">
                    {{ entry.createdAt | moment('LLL') }}
                    {{ $t('from IP') }}
                    {{ entry.ipv4 | intToIp }}
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-xs-6 text-left">
                <a @click="loadPage(page - 1)"
                    v-if="page > 1">&laquo; {{ $t('Newer') }}</a>
            </div>
            <div class="col-xs-6 text-right">
                <a @click="loadPage(page + 1)"
                    v-if="hasMorePages">{{ $t('Older') }} &raquo;</a>
            </div>
        </div>
        <empty-list-placeholder v-if="auditEntries && auditEntries.length === 0"></empty-list-placeholder>
    </loading-cover>
</template>

<script>
    export default {
        props: ['directLink'],
        data() {
            return {
                auditEntries: undefined,
                timer: undefined,
                page: 1,
                hasMorePages: false
            };
        },
        mounted() {
            this.fetch();
        },
        methods: {
            fetch() {
                clearTimeout(this.timer);
                this.timer = undefined;
                this.$http.get(`direct-links/${this.directLink.id}/audit?pageSize=5&page=` + this.page).then(response => {
                    this.auditEntries = response.body;
                    this.timer = setTimeout(() => this.fetch(), 15000);
                    this.hasMorePages = +response.headers.get('X-Total-Count') > this.page * 5;
                });
            },
            loadPage(page) {
                if (this.timer) {
                    this.page = page;
                    this.fetch();
                }
            },
            functionLabel(functionId) {
                return this.$t(this.possibleActions.filter(action => action.id == functionId)[0].caption);
            }
        },
        computed: {
            possibleActions() {
                if (this.directLink) {
                    return [{
                        id: 1000,
                        name: 'READ',
                        caption: 'Read',
                        nameSlug: 'read'
                    }].concat(this.directLink.subject.function.possibleActions);
                }
            },
        },
        beforeDestroy() {
            clearTimeout(this.timer);
        }
    };
</script>
