<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><!-- start: member_login -->
<html xml:lang="en" lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>Looking4Group Progress Realm Forums - Login</title>
<!-- start: headerinclude -->
<link href='//maxcdn.bootstrapcdn.com/font-awesome/4.1.0/css/font-awesome.min.css' rel='stylesheet' type='text/css'>
<link href='http://fonts.googleapis.com/css?family=Source+Sans+Pro:200,300,400,600,700,200italic,400italic,600italic' rel='stylesheet' type='text/css'>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta http-equiv="Content-Script-Type" content="text/javascript" />
<script type="text/javascript" src="images/revolution/jquery-ui.js"></script>
<script type="text/javascript" src="jquery.js"></script>
<link type="text/css" rel="stylesheet" href="css/global.css" />

<script type="text/javascript">
<!--
	lang.unknown_error = "An unknown error has occurred.";

	lang.select2_match = "One result is available, press enter to select it.";
	lang.select2_matches = "{1} results are available, use up and down arrow keys to navigate.";
	lang.select2_nomatches = "No matches found";
	lang.select2_inputtooshort_single = "Please enter one or more character";
	lang.select2_inputtooshort_plural = "Please enter {1} or more characters";
	lang.select2_inputtoolong_single = "Please delete one character";
	lang.select2_inputtoolong_plural = "Please delete {1} characters";
	lang.select2_selectiontoobig_single = "You can only select one item";
	lang.select2_selectiontoobig_plural = "You can only select {1} items";
	lang.select2_loadmore = "Loading more results…";
	lang.select2_searching = "Searching…";

	var cookieDomain = ".looking4group.eu";
	var cookiePath = "/hellfire/";
	var cookiePrefix = "mybb_";
	var deleteevent_confirm = "Are you sure you want to delete this event?";
	var removeattach_confirm = "Are you sure you want to remove the selected attachment from this post?";
	var loading_text = 'Loading. <br />Please Wait..';
	var saving_changes = 'Saving changes..';
	var use_xmlhttprequest = "1";
	var my_post_key = "50f06011eca78f9db35b984f05a914df";
	var rootpath = "http://looking4group.eu/hellfire";
	var imagepath = "http://looking4group.eu/hellfire/images/revolution";
  	var yes_confirm = "Yes";
	var no_confirm = "No";
	var MyBBEditor = null;
	var spinner_image = "http://looking4group.eu/hellfire/images/revolution/spinner.gif";
	var spinner = "<img src='" + spinner_image +"' alt='' />";
// -->
</script>
<!-- end: headerinclude -->
</head>
<body>
<!-- start: header -->
  <div class="float_right" style="margin-top:  60px;"><!-- start: header_welcomeblock_guest -->
<div style="margin-top: 40px; margin-right: 40px;">

</div>


				<div class="modal" id="quick_login" style="display: none;">
						<input name="action" type="hidden" value="do_login" />
						<input name="url" type="hidden" value="" />
						<input name="quick_login" type="hidden" value="1" />
						<table width="100%" cellspacing="0" cellpadding="5" border="0" class="tborder">
							<tr>
								<td class="thead" colspan="2"><strong>Faction Change</strong></td>
							</tr>
							<tr>
								<td class="trow1" width="25%"><strong>Username:</strong></td>
								<td class="trow1"><input name="username" id="quick_login_username" type="text" value="" class="textbox initial_focus" /></td>
							</tr>

							<tr>
								<td class="trow2"><strong>Password:</strong></td>
								<td class="trow2">
								<input name="quick_password" id="password" type="password" value="" class="textbox" />
								</td>
							</tr>

							<tr>
								<td class="trow2"><strong>Character name:</strong></td>
								<td class="trow2">
								<input name="quick_password" id="character_name" type="text" value="" class="textbox" />
								</td>
							</tr>

							<tr>
								<td class="trow2"><strong>Destination Race:</strong></td>
								<td class="trow2">
								<select name="destination_race">
									<option value="Human">Human</option>
									<option value="Dwarf">Dwarf</option>
									<option value="Night Elf">Night Elf</option>
									<option value="Gnome">Gnome</option>
									<option value="Dreanei">Dreanei</option>
									<option value="Orc">Orc</option>
									<option value="Troll">Troll</option>
									<option value="Undead">Undead</option>
									<option value="Tauren">Tauren</option>
									<option value="Blood Elf">Blood Elf</option>
								</select>
								</td>
							</tr>
							<tr>
								<td class="trow2" colspan="2">
									<div align="center"><input name="submit" type="submit" class="button" value="Submit" /></div>
								</td>
							</tr>
						</table>
				</div>
				<script type="text/javascript">
					$("#quick_login input[name='url']").val($(location).attr('href'));
				</script>
<!-- end: header_welcomeblock_guest --></div> 
  </div>
         <br />
          
				
				
				
				
				
              	 <!-- start: nav -->
<!-- end: nav --><br />
                                   
<script type="text/javascript">
// <!--
    if(use_xmlhttprequest == "1")
    {
        $("#extraslink").popupMenu();
    }
// -->
</script>
<!-- end: header -->
<br />
<!-- start: error_inline -->
<div class="error">
	<p><em>Please correct the following errors before continuing:</em></p>
	<ul>
		<li>You have entered an invalid username/password combination. <br /><br />If you have forgotten your password please <a href="member.php?action=lostpw">retrieve a new one</a>.</li>

	</ul>
</div>
<br />
<!-- end: error_inline -->

<table border="0" cellspacing="0" cellpadding="5" class="tborder">
<tr>
<td class="thead" colspan="2"><strong>Faction Change</strong></td>
</tr>

<tr>
	<td class="trow1" width="25%"><strong>Username:</strong></td>
	<td class="trow1"><input name="username" id="username_factionchange" type="text" value="" class="textbox initial_focus" /></td>
</tr>

<tr>
	<td class="trow2"><strong>Password:</strong></td>
	<td class="trow2">
	<input name="password" id="password_factionchange" type="password" value="" class="textbox" />
	</td>
</tr>

<tr>
	<td class="trow2"><strong>Character name:</strong></td>
	<td class="trow2">
	<input name="character_name" id="charname_factionchange" type="text" value="" class="textbox" />
	</td>
</tr>

<tr>
	<td class="trow2"><strong>Destination Race:</strong></td>
	<td class="trow2">
	<select name="destination_race" id="destrace_factionchange">
		<option value="Human">Human</option>
		<option value="Dwarf">Dwarf</option>
		<option value="Night Elf">Night Elf</option>
		<option value="Gnome">Gnome</option>
		<option value="Draenei">Draenei</option>
									<option value="Orc">Orc</option>
									<option value="Troll">Troll</option>
									<option value="Undead">Undead</option>
									<option value="Tauren">Tauren</option>
									<option value="Blood Elf">Blood Elf</option>
	</select>
	</td>
</tr>

</table>
<br />
<div align="center"><input type="submit" class="button" name="submit" value="Submit" /></div>
<!-- start: footer -->
<!-- The following piece of code allows MyBB to run scheduled tasks. DO NOT REMOVE --><!-- End task image code -->

  	
  
</div>
</div>
<br /><center></center><br />
<!-- end: footer -->
</body>
<script>
$(document).ready(function() {

	// Respond to Submit Button Click
	$(".button").click(function() {
		var confirmChange = confirm("WARNING : This change is non reversible. Pressing OK means you have read all of the terms of the Faction Change Service. No reverals will be performed UNDER ANY CIRCUMSTANCE, all changes are final.");
		if (confirmChange == true) {
			// Do Ajax Call
			var confirmChangeTwo = confirm("This serves as your last warning. All factions changes are non reversible, meaning that once your faction changes has been process, there is no going back!");
			if (confirmChangeTwo == true) {
				var user = $('#username_factionchange').val();
				var pass = $('#password_factionchange').val();
				var charname = $('#charname_factionchange').val();
				var destrace = $('#destrace_factionchange').val();
				$.post( "fchange.php", { user: user, pass: pass, charname: charname, destrace: destrace})
					.success(function( data ) {
						alert(data);
					})
					.error(function (data) {
						alert(data);
					});
			}
			else {
				alert("Faction change aborted.");
			}
		}
		else {
			alert("Faction change aborted.");
		}

	});


});


</script>
</html>
<!-- end: member_login -->