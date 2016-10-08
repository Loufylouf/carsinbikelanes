<html>
   <head>
      <link rel="stylesheet" type="text/css" href="../css/style.css" />
      <link href='//fonts.googleapis.com/css?family=Oswald:400,700|Francois+One' rel='stylesheet' type='text/css'>
   </head>

   <body class="non_map">

      <div class="flex_container_dialog">
         <div class="setup_centered">

            <div class="settings_group">
               <form action="index.php" method="post">
                  <h3>Administration</h3>
                  <span>username: </span><input id="username" class="wide" type="text" name="username">
                  <span>password: </span><input id="password" class="wide" type="password" name="password">
                  <input type="submit" class="wide" value="LOGIN">
               </form>
            </div>

         </div>
      </div>

   </body>

   <script type="text/javascript">
      document.getElementById("username").focus();
   </script>
</html>
