<template>
    <div class="footer navbar-fixed-bottom">
        <div class="tasks" id="accordion" role="tablist" aria-multiselectable="true">
            <div class="panel panel-primary">
                <div class="panel-heading c-pointer" role="tab" v-b-toggle.task-collapse variant="primary" aria-expanded="true" aria-controls="collapseOne">
                    <h5 class="panel-title mb-1">
                        <i class="fas fa-tasks fa-fw"></i>{{ translate('misc.tasks.tasks') }}
                    </h5>
                </div>

                <b-collapse id="task-collapse" visible class="mt-2">
                    <div class="panel-collapse tasks-scroll-area" role="tabpanel" aria-labelledby="headingOne">
                        <div class="panel-body ">
                            <table class="table table-hover mb-0" id="tasks-table">
                                <thead>
                                <tr>
                                    <th>{{ translate('misc.start-date') }}</th>
                                    <th>{{ translate('misc.end-date') }}</th>
                                    <th>{{ translate('validation.attributes.user') }}</th>
                                    <th>{{ translate('misc.tasks.task') }}</th>
                                    <th>{{ translate('misc.status') }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                <task
                                        v-for="task in tasks"
                                        :task="task"
                                        :key="task.id"
                                ></task>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </b-collapse>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        created() {
            this.listen();

            this.getTasks();
        },
        data() {
            return {
                tasks: [],
            }
        },
        methods: {
            getTasks() {
                axios.get('/task').then((response) => {
                    this.tasks = response.data;
                }).catch((error) => {
                    console.log(error);
                });
            },
            listen() {
                if (! window.Echo) {
                    this.connectWebsocket();
                }

                window.Echo.private('task')
                    .listen('Task', (e) => {
                        let taskIndex = this.tasks.findIndex(x => x.id === e.task.id);

                        if (this.tasks[taskIndex] === undefined) {
                            this.tasks.unshift(e.task);
                        } else {
                            // Task is already inserted and needs update
                            this.$set(this.tasks, taskIndex, e.task);
                        }
                    });
            }
        }
    }
</script>

<style>
    #tasks-table > tbody > tr > td, #tasks-table > thead > tr > th  {
        padding: 0.25em;
    }

    .tasks  {
        position: absolute;
        width: 100%;
        bottom: 0;
        margin: 0;
        background-color: white;
        border-top: solid 1px #333;
        z-index: 30;
    }

    .tasks-scroll-area {
        height: 250px;
        position: relative;
        overflow: auto;
    }

    .c-pointer {
        cursor: pointer;
    }
</style>
