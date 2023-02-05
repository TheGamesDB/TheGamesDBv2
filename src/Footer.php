<?php

namespace TheGamesDB;

class Footer
{
    public static $_time_start = 0;
    public static function print()
    { global $_user;?>
        <footer class="container-fluid bg-dark" style="margin-top:10px; padding: 20px;">
            <div class="container">
                <div class="row">
                    <div class="col-sm-3">
                        <h2 class="logo"><a href="/"> TheGamesDB </a></h2>
                    </div>
                    <div class="col-sm-2">
                        <h5>Get started</h5>
                        <ul>
                            <li><a href="/">Home</a></li>
                            <?php if(empty($_user) || !$_user->isLoggedIn()) : ?>
                                <li><a href="https://forums.thegamesdb.net/ucp.php?mode=register">Sign up</a></li>
                            <?php endif; ?>
                            <li><a href="/browse.php">Games</a></li>
                            <li><a href="/list_platforms.php">Platforms</a></li>
                            <li><a href="/list_devs.php">Developers</a></li>
                            <li><a href="/list_pubs.php">Publishers</a></li>
                        </ul>
                    </div>
                    <div class="col-sm-3">
                        <h5>Developers</h5>
                        <ul>
                            <li><a href="https://api.thegamesdb.net/">API Documentation</a></li>
                            <li><a href="https://forums.thegamesdb.net/viewforum.php?f=10">API Access Request</a></li>
                            <li><a href="https://github.com/TheGamesDB/TheGamesDBv2">Github Repo</a></li>
                        </ul>
                    </div>
                    <?php if(false) : ?>
                        <div class="col-sm-2">
                            <h5>About us</h5>
                            <ul>
                                <li><a href="#">Company Information</a></li>
                                <li><a href="#">Contact us</a></li>
                                <li><a href="#">Reviews</a></li>
                            </ul>
                        </div>
                        <div class="col-sm-2">
                            <h5>Support</h5>
                            <ul>
                                <li><a href="#">FAQ</a></li>
                                <li><a href="#">Help desk</a></li>
                                <li><a href="https://forums.thegamesdb.net/">Forums</a></li>
                            </ul>
                        </div>
                    <?php endif;?>
                    <div class="col-sm-3">
                        <div class="social-networks">
                            <a href="https://twitter.com/thegamesdb" class="twitter"><i class="fab fa-twitter"></i></a>
                            <a href="https://www.facebook.com/thegamesdb/" class="facebook"><i class="fab fa-facebook"></i></a>
                        </div>
                        <a href="https://forums.thegamesdb.net/memberlist.php?mode=contactadmin" role="button" class="btn btn-info">Contact us</a>
                    </div>
                </div>

                <div class="footer-copyright">
                    <p>© <?= date("Y") ?> TheGamesDB </p>
                </div>
                <p>execution time: <?= microtime(true) - Footer::$_time_start ?></p>
            </div>
        </footer>
        </body>
        </html>
    <?php }
}