function displayMessage(author, text)
{
    document.getElementById("messages-container").insertAdjacentHTML("beforeend",
    `<div class="message">
    <div class="author">`+author+`</div>
    <div class="text">`+text+`</div>
    </div>`);
}

let messageBuffer;
const ws = new WebSocket("ws://"+location.host);
const msgForm = document.forms["messaging-form"];

msgForm.addEventListener("submit", function(e)
{
    e.preventDefault();
});

msgForm.addEventListener("submit", function(e)
{
    const inputBox = msgForm.elements["text"];
    messageBuffer = inputBox.value
    inputBox.value = "";
    ws.send(messageBuffer);
});

ws.addEventListener("message", function(e)
{
    const data = JSON.parse(e.data);
    
    if( data.text === true )
    {
        displayMessage(data.author, messageBuffer);
        return;
    }
    
    displayMessage(data.author, data.text);
});
