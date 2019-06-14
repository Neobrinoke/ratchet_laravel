<template>
    <div class="messaging">
        <div class="inbox_msg">
            <div class="inbox_people">
                <div class="headind_srch">
                    <div class="recent_heading">
                        <h4>Recent</h4>
                    </div>
                </div>
                <div class="inbox_chat">
                    <div class="chat_list" :class="{ active_chat: user.id === toId }" v-for="user in users" v-if="user.id !== userId" @click.prevent="onUserChat(user.id)">
                        <div class="chat_people">
                            <div class="chat_img">
                                <img src="https://ptetutorials.com/images/user-profile.png" alt="sunil">
                                <br>
                                <span class="badge badge-primary badge-pill" v-show="user.unreadMessages > 0">{{ user.unreadMessages }}</span>
                            </div>
                            <div class="chat_ib">
                                <h5>{{ user.name }} <span class="chat_date">Dec 25</span></h5>
                                <p>Last message</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <message
                    v-if="!!toId"
                    @on-mounted="handleMountedMessageComponent($event)"
                    :user-id="userId"
                    :to-id="toId"
                    :ws="ws"
            ></message>
        </div>
    </div>
</template>

<script>
    import Message from './Message';

    export default {
        name: 'Chat',
        components: {
            Message,
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
                ws: null,
                users: JSON.parse(this.userData),
                toId: null,
                messageComponentInstance: null,
            };
        },
        mounted() {
            this.ws = new WebSocket('ws://localhost:8090');
            this.ws.onopen = this.onOpen;
            this.ws.onmessage = this.onMessage;

            this.users = this.users.map((user) => {
                user.unreadMessages = 0;

                return user;
            });
        },
        methods: {
            onOpen(e) {
                console.log('Connection established !');
                this.ws.send(JSON.stringify({action: 'register', userId: this.userId}));
            },
            onMessage(e) {
                let message = JSON.parse(e.data);
                if (message.action) {
                    switch (message.action) {
                        case 'message':
                            if (!this.messageComponentInstance || this.toId !== message.from) {
                                this.users = this.users.map((user) => {
                                    if (user.id === message.from) {
                                        user.unreadMessages = user.unreadMessages + 1;
                                    }

                                    return user;
                                });
                            } else {
                                this.messageComponentInstance.handleReceiveMessage(message);
                            }

                            break;
                    }
                }
            },
            onUserChat(toId) {
                this.toId = toId;

                this.users = this.users.map((user) => {
                    if (user.id === this.toId) {
                        user.unreadMessages = 0;
                    }

                    return user;
                });
            },
            handleMountedMessageComponent(instance) {
                this.messageComponentInstance = instance;
            }
        },
    }
</script>

<style>
    img {
        max-width: 100%;
    }

    .inbox_people {
        background: #f8f8f8 none repeat scroll 0 0;
        float: left;
        overflow: hidden;
        width: 40%;
        border-right: 1px solid #c4c4c4;
    }

    .inbox_msg {
        border: 1px solid #c4c4c4;
        clear: both;
        overflow: hidden;
    }

    .top_spac {
        margin: 20px 0 0;
    }

    .recent_heading {
        float: left;
        width: 40%;
    }

    .srch_bar {
        display: inline-block;
        text-align: right;
        width: 60%;
        padding:
    }

    .headind_srch {
        padding: 10px 29px 10px 20px;
        overflow: hidden;
        border-bottom: 1px solid #c4c4c4;
    }

    .recent_heading h4 {
        color: #05728f;
        font-size: 21px;
        margin: auto;
    }

    .srch_bar input {
        border: 1px solid #cdcdcd;
        border-width: 0 0 1px 0;
        width: 80%;
        padding: 2px 0 4px 6px;
        background: none;
    }

    .srch_bar .input-group-addon button {
        background: rgba(0, 0, 0, 0) none repeat scroll 0 0;
        border: medium none;
        padding: 0;
        color: #707070;
        font-size: 18px;
    }

    .srch_bar .input-group-addon {
        margin: 0 0 0 -27px;
    }

    .chat_ib h5 {
        font-size: 15px;
        color: #464646;
        margin: 0 0 8px 0;
    }

    .chat_ib h5 span {
        font-size: 13px;
        float: right;
    }

    .chat_ib p {
        font-size: 14px;
        color: #989898;
        margin: auto
    }

    .chat_img {
        float: left;
        width: 11%;
    }

    .chat_ib {
        float: left;
        padding: 0 0 0 15px;
        width: 88%;
    }

    .chat_people {
        overflow: hidden;
        clear: both;
    }

    .chat_list {
        border-bottom: 1px solid #c4c4c4;
        margin: 0;
        padding: 18px 16px 10px;
    }

    .chat_list:hover {
        background: #ebebeb;
        cursor: pointer;
    }

    .inbox_chat {
        height: 550px;
        overflow-y: scroll;
    }

    .active_chat {
        background: #ebebeb;
    }

    .incoming_msg_img {
        display: inline-block;
        width: 6%;
    }

    .received_msg {
        display: inline-block;
        padding: 0 0 0 10px;
        vertical-align: top;
        width: 92%;
    }

    .received_withd_msg p {
        background: #ebebeb none repeat scroll 0 0;
        border-radius: 3px;
        color: #646464;
        font-size: 14px;
        margin: 0;
        padding: 5px 10px 5px 12px;
        width: 100%;
    }

    .time_date {
        color: #747474;
        display: block;
        font-size: 12px;
        margin: 8px 0 0;
    }

    .received_withd_msg {
        width: 57%;
    }

    .mesgs {
        float: left;
        padding: 30px 15px 0 25px;
        width: 60%;
    }

    .sent_msg p {
        background: #05728f none repeat scroll 0 0;
        border-radius: 3px;
        font-size: 14px;
        margin: 0;
        color: #fff;
        padding: 5px 10px 5px 12px;
        width: 100%;
    }

    .outgoing_msg {
        overflow: hidden;
        margin: 13px 0 13px;
    }

    .incoming_msg {
        margin: 13px 0 13px;
    }

    .sent_msg {
        float: right;
        width: 46%;
    }

    .input_msg_write input {
        background: rgba(0, 0, 0, 0) none repeat scroll 0 0;
        border: medium none;
        color: #4c4c4c;
        font-size: 15px;
        min-height: 48px;
        width: 100%;
    }

    .type_msg {
        border-top: 1px solid #c4c4c4;
        position: relative;
    }

    .msg_send_btn {
        background: #05728f none repeat scroll 0 0;
        border: medium none;
        border-radius: 50%;
        color: #fff;
        cursor: pointer;
        font-size: 17px;
        height: 33px;
        position: absolute;
        right: 0;
        top: 11px;
        width: 33px;
    }

    .messaging {
        padding: 0 0 50px 0;
    }

    .msg_history {
        height: 516px;
        overflow-y: auto;
    }
</style>
