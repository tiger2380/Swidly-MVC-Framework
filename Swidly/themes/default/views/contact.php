<?php

echo 'contact page';
?>
<form method="POST">
    <input type="hidden" name="<?= \Swidly\Core\Store::csrf() ?>">
    <label>First Name</label>
    <input type="text" name="first_name" placeholder="Your name.."><br/>
    <label>Last Name</label>
    <input type="text" name="last_name" placeholder="Your last name.."><br/>
    <label>Email</label>
    <input type="email" name="email" placeholder="Your email"><br/>
    <label>Subject</label>
    <textarea name="subject" placeholder="Write something.." style="height:200px"></textarea><br/>
    <input type="submit" value="Submit">
</form>