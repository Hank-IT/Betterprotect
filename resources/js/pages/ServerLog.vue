<template>
    <div class="server.log">
        <div class="toolbar">
            <b-row>
                <b-col md="1">
                    <b-btn class="btn-primary" @click="refreshLogs"><i class="fas fa-sync"></i></b-btn>
                </b-col>

                <b-col md="6" offset="5">
                    <b-form-group >
                        <b-input-group>
                            <b-input-group-prepend>
                                    <date-range-picker
                                            :maxDate="maxDate"
                                            :locale-data="locale"
                                            :opens="opens"
                                            @update="updateSelectedDate"
                                    >
                                        <!--Optional scope for the input displaying the dates -->
                                        <div slot="input" slot-scope="picker">{{ currentSelected }}</div>
                                    </date-range-picker>
                            </b-input-group-prepend>
                            <b-form-input v-model="search" placeholder="Suche" @change="getLogs(currentPage)"/>
                            <b-input-group-append>
                                <b-btn>Leeren</b-btn>
                            </b-input-group-append>
                        </b-input-group>
                    </b-form-group>
                </b-col>
            </b-row>
        </div>

        <b-table hover :items="logs" :fields="fields" @row-clicked="showModal" :sort-by.sync="sortBy" :sort-desc.sync="sortDesc" v-if="!logsLoading">
            <template slot="from" slot-scope="data">
                <span v-b-popover.hover="data.item.from" v-if="data.item.from">
                    {{ data.item.from.trunc(40) }}
                </span>
            </template>
            <template slot="to" slot-scope="data">
                <span v-b-popover.hover="data.item.to" v-if="data.item.to">
                    {{ data.item.to.trunc(40) }}
                </span>
            </template>
            <template slot="client" slot-scope="data">
                <span v-b-popover.hover="data.item.client" v-if="data.item.client">
                    {{ data.item.client.trunc(40) }}
                </span>
            </template>
            <template slot="status" slot-scope="data">
                <p class="text-danger" v-if="data.item.status === 'reject'">{{ data.item.status }}</p>
                <p class="text-warning" v-else-if="data.item.status === 'deferred'">{{ data.item.status }}</p>
                <p class="text-success" v-else-if="data.item.status === 'sent'">{{ data.item.status }}</p>
                <p class="text-secondary" v-else>{{ data.item.status }}</p>
            </template>
        </b-table>

        <b-pagination size="md" :total-rows="totalRows" v-model="currentPage" :per-page="perPage" v-on:change="getLogs" v-if="!logsLoading"></b-pagination>

        <div class="text-center" v-if="logsLoading">
            <div class="spinner-border" style="width: 3rem; height: 3rem;" role="status">
                <span class="sr-only">Loading...</span>
            </div>
        </div>

        <b-modal id="mail-log-modal" ref="mailLogModal" size="xl" title="Mail Log">
            <div class="stepwizard col-md-offset-3">
                <div class="stepwizard-row">
                    <div class="stepwizard-step">
                        <p>{{ detailedRow.client }}&nbsp;</p>
                        <button type="button" class="btn btn-circle btn-secondary" :class="{ 'btn-success': detailedRow.status === 'sent', 'btn-warning': detailedRow.status === 'deferred', 'btn-danger': detailedRow.status === 'reject' }">Client</button>
                        <p class="stepwizard-step-subtitle">{{ detailedRow.from }}</p>
                    </div>
                    <div class="stepwizard-step">
                        <p>&nbsp;</p>
                        <button type="button" class="btn btn-circle btn-secondary" :class="{ 'btn-success': detailedRow.status === 'sent', 'btn-warning': detailedRow.status === 'deferred', 'btn-danger': detailedRow.status === 'reject' }">Server</button>
                        <p class="stepwizard-step-subtitle">{{ detailedRow.host }}</p>
                    </div>
                    <div class="stepwizard-step">
                        <p>{{ detailedRow.relay }}&nbsp;</p>
                        <button type="button" class="btn btn-circle btn-secondary" :class="{ 'btn-success': detailedRow.status === 'sent', 'btn-warning': detailedRow.status === 'deferred', 'btn-danger': detailedRow.status === 'reject' }">Relay</button>
                        <p class="stepwizard-step-subtitle">{{ detailedRow.to }}</p>
                    </div>
                </div>
            </div>

            <b-card title="Details">
                <div class="card-text">
                    <table class="table">
                        <tr>
                            <th>Erhalten am</th>
                            <td>{{ detailedRow.reported_at }}</td>
                        </tr>
                        <tr>
                            <th>Queue ID</th>
                            <td>{{ detailedRow.queue_id }}</td>
                        </tr>
                        <tr>
                            <th>Code</th>
                            <td>{{ detailedRow.dsn }}</td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td>{{ detailedRow.status }}</td>
                        </tr>
                        <tr>
                            <th>Helo</th>
                            <td>{{ detailedRow.helo }}</td>
                        </tr>
                        <tr>
                            <th>Größe (Bytes)</th>
                            <td>{{ detailedRow.size }}</td>
                        </tr>
                        <tr>
                            <th>Delays</th>
                            <td>{{ detailedRow.delays }}</td>
                        </tr>
                        <tr>
                            <th>Empfänger (Anzahl)</th>
                            <td>{{ detailedRow.nrcpt }}</td>
                        </tr>
                    </table>

                    <hr>

                    <h5>Antwort:</h5>

                    <p>{{ detailedRow.response }}</p>
                </div>
            </b-card>

            <div slot="modal-footer"></div>
        </b-modal>
    </div>
</template>

<script>
    export default {
        created() {
            this.getLogs(this.currentPage);
        },
        data() {
            return {
                /**
                 * Pagination
                 */
                currentPage: 1,
                perPage: 10,
                totalRows: null,
                sortBy: 'reported_at',
                sortDesc: true,

                /**
                 * Search
                 */
                search: null,

                /**
                 * Datepicker
                 */
                currentSelected: this.moment().format('YYYY-MM-DD'),
                maxDate: this.moment().format('YYYY-MM-DD'),
                startDate: this.moment().format('YYYY-MM-DD'),
                endDate: this.moment().format('YYYY-MM-DD'),
                opens: 'right',
                locale: {
                    direction: 'ltr', //direction of text
                    format: 'DD-MM-YYYY HH:mm', //fomart of the dates displayed
                    separator: ' - ', //separator between the two ranges
                    applyLabel: 'Apply',
                    cancelLabel: 'Cancel',
                    weekLabel: 'W',
                    daysOfWeek: this.moment.weekdaysMin(), //array of days - see moment documenations for details
                    monthNames: this.moment.monthsShort(), //array of month names - see moment documenations for details
                    firstDay: 1 //ISO first day of week - see moment documenations for details
                },

                logs: [],
                logsLoading: false,
                fields: [
                    {
                        key: 'reported_at',
                        label: 'Erhalten am',
                        'class': 'col-reported_at',
                    },
                    {
                        key: 'from',
                        label: 'Absender',
                        'class': 'col-from',
                    },
                    {
                        key: 'to',
                        label: 'Empfänger',
                        'class': 'col-to',
                    },
                    {
                        key: 'client',
                        label: 'Client',
                        'class': 'col-client',
                    },
                    {
                        key: 'status',
                        label: 'Status',
                        sortable: true,
                    },
                ],
                detailedRow: [],
            }
        },
        methods: {
            refreshLogs() {
                this.getLogs(this.currentPage);
            },
            getLogs(currentPage) {
                this.currentPage = currentPage;

                this.logsLoading = true;

                axios.get('/server/log', {
                    params: {
                        startDate: this.startDate,
                        endDate: this.endDate,
                        currentPage: this.currentPage,
                        perPage: this.perPage,
                        sortBy: this.sortBy,
                        sortDesc: this.sortDesc,
                        search: this.search,
                    }
                }).then((response) => {
                    this.logs = Object.values(response.data.data.data);
                    console.log(this.logs);
                    this.totalRows = response.data.data.total;
                    this.logsLoading = false;
                }).catch((error) => {
                    console.log(error);
                    this.logsLoading = false;
                });
            },
            showModal(record, index) {
                this.$refs.mailLogModal.show();
                this.detailedRow = record;
            },
            updateSelectedDate(data) {
                this.startDate = this.moment(data.startDate).format('YYYY-MM-DD');
                this.endDate = this.moment(data.endDate).format('YYYY-MM-DD');

                if (data.startDate.getTime() === data.endDate.getTime()) {
                    this.currentSelected = this.startDate;
                } else {
                    this.currentSelected = this.startDate + ' - ' + this.endDate;
                }

                this.getLogs(this.currentPage);
            }
        }
    }
</script>

<style>
    .stepwizard-step-subtitle {
        margin-top: 10px;
    }
    .stepwizard-row {
        display: table-row;
    }
    .stepwizard {
        display: table;
        width: 100%;
        position: relative;
    }
    .stepwizard-row:before {
        top: 55px;
        bottom: 0;
        position: absolute;
        content: " ";
        width: 100%;
        height: 1px;
        background-color: #ccc;
        z-order: 0;
    }
    .stepwizard-step {
        display: table-cell;
        text-align: center;
        position: relative;
        width: 375px;
    }
    .btn-circle {
        width: 120px;
        height: 30px;
        text-align: center;
        padding: 6px 0;
        font-size: 12px;
        line-height: 1.428571429;
        border-radius: 15px;
    }

    .col-reported_at {
        max-width: 90px;
    }

    .col-from {
        max-width: 190px;
    }

    .col-to {
        max-width: 190px;
    }

    .col-client {
        max-width: 190px;
    }
</style>