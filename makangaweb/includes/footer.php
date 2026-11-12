</main>

<footer class="site-footer">
    <div class="container footer-grid footer-grid-4">
        <div>
            <a href="index.php" class="brand brand-footer">
                <span class="brand-mark">A</span>
                <span class="brand-name"><span>ACROBATIC</span><em>TECH HUB</em></span>
            </a>
            <p>Websites and web systems built for businesses, schools and organisations in Soroti City and Eastern Uganda.</p>
        </div>
        <div>
            <h4>Explore</h4>
            <ul>
                <li><a href="about.php">About us</a></li>
                <li><a href="projects.php">Projects</a></li>
                <li><a href="trainings.php">Trainings</a></li>
                <li><a href="contact.php">Contact</a></li>
            </ul>
        </div>
        <div>
            <h4>Account</h4>
            <ul>
                <li><a href="login.php">Login</a></li>
                <li><a href="register.php">Register</a></li>
                <li><a href="profile.php">My profile</a></li>
            </ul>
        </div>
        <div>
            <h4>Visit us</h4>
            <ul>
                <li>Soroti City, Teso Sub-region</li>
                <li>Uganda</li>
                <li>Mon - Sat, 8:00 AM - 6:00 PM</li>
            </ul>
        </div>
    </div>
    <div class="footer-bottom">
        <div class="container">&copy; <?= date('Y') ?> Acrobatic Tech Hub. All rights reserved.</div>
    </div>
</footer>

<div class="chat" id="chat">
    <div class="chat-panel" id="chatPanel" role="dialog" aria-label="Customer Support" hidden>
        <div class="chat-head"><strong>Customer Support</strong><button type="button" id="chatClose" aria-label="Close chat">&times;</button></div>
        <div class="chat-body" id="chatBody">
            <div class="chat-meta">Customer Support</div>
            <div class="chat-msg">Welcome to our site, if you need help simply type a message below and we will take it from there.</div>
        </div>
        <form class="chat-form" id="chatForm" action="contact.php" method="get">
            <input type="text" name="msg" id="chatInput" placeholder="Type here and press enter.." autocomplete="off" maxlength="500" required>
            <button type="submit" aria-label="Send">&#10148;</button>
        </form>
    </div>
    <button type="button" class="chat-launch" id="chatLaunch" aria-label="Open chat" aria-expanded="false">
        <svg viewBox="0 0 24 24" width="30" height="30" fill="none" stroke="#fff" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 9l7 7 7-7"/></svg>
    </button>
</div>

<script src="js/main.js?v=<?= (int)filemtime(__DIR__ . '/../js/main.js') ?>"></script>
</body>
</html>
