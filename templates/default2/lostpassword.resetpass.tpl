<form name="resetpass"" method="POST">
<input type="hidden" name="type" value="setnewpass" />
<input type="hidden" name="resetcode" value="{resetCode}" />
    <div class="full">
        <div class="pad20_f">
            <span style="font-size:16px;"><b>{l_auth_enter_new_password}:</b></span>
            <br/><br/>
            {l_auth_pass}: <br/> <input type="password" name="password">
            <br/><br/>
            {l_auth_pass2}: <br/> <input type="password" name="password2">
            <br/><br/>
            <div><input type="submit" class="btn btn-primary btn-large" value="{l_auth_set_new_password}"/></div>
        </div>
    </div>
</form>
