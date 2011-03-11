function viewThread(id) {

        document.pageForm.threadId.value = id;
        submitForm('viewThread');

}

function createTopic() {

        document.pageForm.threadId.value = '';
        document.pageForm.messageId.value = '';
        submitForm('entry');

}



function postReply(id) {

        document.getElementById("threadId").value = id;
        document.getElementById("messageId").value = '';
        submitForm('entry');

}

function changePost(id) {

        document.getElementById("messageId").value = id;
        submitForm('changePost');

}

function editMessage(id) {
        document.pageForm.messageId.value = id;
        submitForm('entry');
}

function removeMessage(id) {

        if (!confirm(I18N["discuss_msg_remove"])) return false;

        document.getElementById("messageId").value = id;
        submitForm('removePost');


}
