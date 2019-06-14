<template>
    <div class="conversation_block">
        <div class="conversation_box">
            <div class="conversation_header">
                <h4>Recent</h4>
                <div class="dropdown float-right">
                    <button class="btn no-shadow btn-secondary btn-sm dropdown-toggle" type="button" data-toggle="dropdown">
                        <i class="fa fa-plus"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-right">
                        <a class="dropdown-item" href="#" v-for="user in users" v-if="user.id !== userId" @click.prevent="createConversation(user.id, user.name)">{{ user.name }}</a>
                    </div>
                </div>
            </div>
            <div class="conversation_body">
                <div class="conversation_list" v-for="conversation in conversations" :class="{ active: currentConversationId === conversation.id }" @click.prevent="changeConversation(conversation.id)">
                    <div class="conversation_element">
                        <img src="https://ptetutorials.com/images/user-profile.png" alt="sunil">
                        <div class="conversation_details">
                            <h5>{{ conversation.name }} <span>Dec 25</span></h5>
                            <p><span class="badge badge-primary badge-pill" v-show="conversation.unread_message_count > 0">{{ conversation.unread_message_count }}</span> {{ conversation.last_message }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <component
                v-for="conversation in conversations"
                v-show="currentConversationId === conversation.id"
                :is="conversation.model"
                :key="conversation.id"
                :user-id="userId"
                :to-id="conversation.toId"
                :ws="ws"
                @on-mounted="initConversation($event, conversation)"
        ></component>
    </div>
</template>

<script>
    import Conversation from './Conversation';
    import UuidV4 from 'uuid/v4';

    export default {
        name: 'Chat',
        components: {
            Conversation,
        },
        props: {
            userData: {
                type: String,
                required: true,
            },
            userId: {
                type: Number,
                required: true,
            },
        },
        data() {
            return {
                users: JSON.parse(this.userData),
                ws: null,
                currentConversationId: null,
                conversations: [],
            };
        },
        mounted() {
            this.ws = new WebSocket('ws://localhost:8090');
            this.ws.onopen = this.onWsOpen;
            this.ws.onmessage = this.onWsMessage;

            this.users = this.users.map((user) => {
                user.unread_messages = 0;
                user.last_message = '';

                return user;
            });
        },
        methods: {
            onWsOpen(e) {
                console.log('Connection established !');
                this.ws.send(JSON.stringify({action: 'register', userId: this.userId}));
            },
            onWsMessage(e) {
                let message = JSON.parse(e.data);
                if (message.action) {
                    switch (message.action) {
                        case 'message':
                            return this.onMessageAction(message);
                    }
                }
            },
            onMessageAction(message) {
                let conversation = this.conversations.find(co => co.toId === message.from);
                if (!conversation) {
                    conversation = {
                        id: UuidV4(),
                        name: 'Nouvelle conversation',
                        toId: message.from,
                        model: Conversation,
                        instance: null,
                        unread_message_count: 0,
                        last_message: '',
                    };

                    this.conversations.push(conversation);
                }

                if (conversation.id !== this.currentConversationId) {
                    this.conversations = this.conversations.map((conv) => {
                        if (conv.toId === message.from) {
                            conv.unread_message_count = conv.unread_message_count + 1;
                            conv.last_message = message.content;
                        }

                        return conv;
                    });
                }

                this.$nextTick(() => {
                    conversation.instance.handleReceiveMessage(message);
                });

            },
            changeConversation(conversationId) {
                this.conversations = this.conversations.map((conversation) => {
                    if (conversation.id === conversationId) {
                        conversation.unread_message_count = 0;
                    }

                    return conversation;
                });

                this.currentConversationId = conversationId;
            },
            initConversation(instance, conversation) {
                conversation.instance = instance;
            },
            createConversation(toId, name) {
                let conversation = this.conversations.find(co => co.toId === toId);
                if (!conversation) {
                    conversation = {
                        id: UuidV4(),
                        name: name,
                        toId: toId,
                        model: Conversation,
                        instance: null,
                        unread_message_count: 0,
                        last_message: '',
                    };

                    this.conversations.push(conversation);
                }

                this.changeConversation(conversation.id);
            },
        },
    }
</script>

<style lang="scss" scoped>
    .no-shadow {
        -webkit-box-shadow: none !important;
        -moz-box-shadow: none !important;
        box-shadow: none !important;
    }

    img {
        max-width: 100%;
    }

    .conversation_block {
        border: 1px solid #c4c4c4;
        clear: both;
        overflow: hidden;

        .conversation_box {
            background: #f8f8f8 none repeat scroll 0 0;
            float: left;
            overflow: hidden;
            width: 40%;
            border-right: 1px solid #c4c4c4;

            .conversation_header {
                padding: 10px 29px 10px 20px;
                border-bottom: 1px solid #c4c4c4;

                h4 {
                    color: #05728f;
                    font-size: 23px;
                    margin: auto;
                    display: inline-block;
                }
            }

            .conversation_body {
                height: 550px;
                overflow-y: scroll;

                .conversation_list {
                    border-bottom: 1px solid #c4c4c4;
                    margin: 0;
                    padding: 18px 16px 10px;

                    &:hover {
                        background: #ebebeb;
                        cursor: pointer;
                    }

                    &.active {
                        background: #ebebeb;
                    }

                    .conversation_element {
                        overflow: hidden;
                        clear: both;

                        img {
                            float: left;
                            width: 11%;
                        }

                        .conversation_details {
                            float: left;
                            padding: 0 0 0 15px;
                            width: 88%;

                            p {
                                font-size: 14px;
                                color: #989898;
                                margin: auto
                            }

                            h5 {
                                font-size: 15px;
                                color: #464646;
                                margin: 0 0 8px 0;

                                span {
                                    font-size: 13px;
                                    float: right;
                                }
                            }
                        }
                    }
                }
            }
        }
    }
</style>
