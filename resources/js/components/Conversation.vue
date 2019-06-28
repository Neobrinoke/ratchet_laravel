<template>
    <div class="conversation_block" v-if="wsInstance && ready">
        <div class="conversation_box">
            <div class="conversation_header">
                <h4>Vos conversations</h4>
                <button class="btn no-shadow btn-secondary btn-sm float-right" type="button" data-toggle="modal" data-target="#newChatModal">
                    <i class="fa fa-plus"></i>
                </button>
            </div>
            <div class="conversation_body">
                <div class="conversation_list" v-for="chat in chats" :class="{ active: currentChat && currentChat.id === chat.id }" @click.prevent="changeChat(chat)">
                    <div class="conversation_element">
                        <img src="https://ptetutorials.com/images/user-profile.png" alt="sunil">
                        <div class="conversation_details">
                            <h5>{{ chat.name }} <span>Dec 25</span></h5>
                            <p v-show="chat.unread_message_count > 0"><span class="badge badge-primary badge-pill">{{ chat.unread_message_count }}</span> {{ chat.last_message }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <chat
                v-for="chat in chats"
                v-show="currentChat && currentChat.id === chat.id"
                :key="chat.id"
                :chat="chat"
                :current-user="currentUser"
                :ws-instance="wsInstance"
                :users="users"
                @on-mounted="initChat($event, chat)"
        ></chat>

        <div class="modal" id="newChatModal">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">Nouveau chat</h4>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="chat_name">Nom du chat</label>
                            <input type="text" id="chat_name" class="form-control" v-model="newChatName">
                        </div>
                        <div class="form-group">
                            <label for="chat_members">Sélectionnez des membres à ajouter au chat</label>
                            <select id="chat_members" class="form-control" v-model="newChatMembers" multiple>
                                <option v-for="user in users" v-if="user.id !== currentUser.id" :value="user.id">{{ user.name }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-dismiss="modal">Annuler</button>
                        <button type="button" class="btn btn-success" @click.prevent="createChat()">Créer le chat</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    const ACTION_REGISTER_USER = 'register_user';
    const ACTION_CREATE_CHAT = 'create_chat';
    const ACTION_RECEIVE_MESSAGE = 'receive_message';
    const ACTION_RECEIVE_NOTIFICATION = 'receive_notification';

    import Chat from './Chat';

    export default {
        name: 'Conversation',
        components: {
            Chat,
        },
        props: {
            currentUserData: {
                type: String,
                required: true,
            },
            userData: {
                type: String,
                required: true,
            },
            chatData: {
                type: String,
                required: true,
            },
        },
        data() {
            return {
                currentUser: JSON.parse(this.currentUserData),
                users: JSON.parse(this.userData),
                chats: JSON.parse(this.chatData),
                wsInstance: null,
                ready: false,
                currentChat: null,
                newChatName: '',
                newChatMembers: [],
            };
        },
        mounted() {
            this.wsInstance = new WebSocket('ws://localhost:8090');
            this.wsInstance.onopen = this.onWsOpen;
            this.wsInstance.onmessage = this.onWsMessage;

            this.chats = this.chats.map((chat) => {
                chat.instance = null;
                chat.is_load = false;
                chat.last_message = '';
                chat.unread_message_count = 0;

                return chat;
            });
        },
        methods: {
            onWsOpen(e) {
                console.log('Connection established !');

                this.wsInstance.send(JSON.stringify({
                    action: ACTION_REGISTER_USER,
                    user_id: this.currentUser.id,
                }));

                this.ready = true;
            },
            onWsMessage(e) {
                let data = JSON.parse(e.data);
                if (data.action) {
                    switch (data.action) {
                        case ACTION_RECEIVE_MESSAGE:
                            return this.onReceiveMessage(data.message);
                        case ACTION_RECEIVE_NOTIFICATION:
                            return this.onReceiveNotification(data.notification);
                        case ACTION_CREATE_CHAT:
                            return this.onCreateChat(data.chat);
                    }
                }
            },
            onReceiveMessage(message) {
                if (this.currentChat && this.currentChat.id === message.chat_id) {
                    this.currentChat.instance.handleReceiveMessage(message);
                    this.currentChat.updated_at = message.created_at;
                } else {
                    this.chats = this.chats.map((chat) => {
                        if (chat.id === message.chat_id) {
                            chat.last_message = message.content;
                            chat.unread_message_count = chat.unread_message_count + 1;
                            chat.updated_at = message.created_at;

                            if (chat.is_load) {
                                chat.instance.handleReceiveMessage(message);
                            }
                        }

                        return chat;
                    });
                }

                // Reorder chats for sorted by updated_at.
                this.chats = this.chats.sort((a, b) => {
                    return new Date(b.updated_at) - new Date(a.updated_at);
                });
            },
            onReceiveNotification(notification) {
                this.chats = this.chats.map((chat) => {
                    if (chat.id === notification.chat_id) {
                        chat.last_message = notification.content;
                        chat.unread_message_count = chat.unread_message_count + 1;
                        chat.updated_at = notification.created_at;

                        if (chat.is_load) {
                            chat.instance.handleReceiveNotification(notification);
                        }
                    }

                    return chat;
                });

                // Reorder chats for sorted by updated_at.
                this.chats = this.chats.sort((a, b) => {
                    return new Date(b.updated_at) - new Date(a.updated_at);
                });
            },
            onCreateChat(chat) {
                chat.instance = null;
                chat.last_message = '';
                chat.unread_message_count = 0;
                chat.is_load = false;

                this.chats.unshift(chat);
            },
            changeChat(nextChat) {
                nextChat.last_message = '';
                nextChat.unread_message_count = 0;

                this.currentChat = nextChat;
                if (!this.currentChat.is_load && this.currentChat.instance) {
                    this.currentChat.instance.loadMessages();
                }
            },
            initChat(instance, chat) {
                chat.instance = instance;
            },
            createChat() {
                if (!this.newChatName) {
                    return;
                }

                this.wsInstance.send(JSON.stringify({
                    action: ACTION_CREATE_CHAT,
                    name: this.newChatName,
                    members: this.newChatMembers,
                }));

                this.newChatName = '';
                this.newChatMembers = [];

                $('#newChatModal').modal('hide');
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
