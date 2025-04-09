function showId2()
{
    var userId = $("#uId").val();
    if (userId != "")
    {
        $("#idmsg").show();
        $("#idmsg").html("<font color='#0000ff'>" + userId + "</font>");
    }else
    {
        $("idmsg").hide();
    }
}

function showId()
{
    // 宣告變數:let,var均可
    // .getElementById 用於一般參數取值,.getElementByNames 用於陣列參數取值
    var userId = document.getElementById("uId").value;
    if (userId != "")
    {
        //inline:同一行,block:換行
        // document.getElementById("idmsg").style.display = "inline";
        document.getElementById("idmsg").style.display = "block";
        document.getElementById("idmsg").innerHTML = "<font color='#0000ff'>" + userId + "</font>";
    }else{
        // 身份證欄位未輸入資料時，將id=idmsg 隱藏
        document.getElementById("idmsg").style.display = "none";
    }
    
}