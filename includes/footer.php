<?php
/**
 * includes/footer.php
 * ------------------------------------------------------------
 * PURPOSE: Shared HTML footer included at the bottom of every page.
 *
 * INCLUDED BY: Every page in the site (after all content).
 *
 * 
 * ------------------------------------------------------------
 */
?>

<!-- ============================================================
     GLOBAL FOOTER
     Soft pink-lavender band with centred text.
     Appears at the bottom of every page.
     ============================================================ -->
<footer style="
    text-align: center;
    padding: 26px 20px;
    margin-top: 60px;
    color: #8b7b98;
    font-size: 13px;
    font-family: 'Georgia', serif;
    background: linear-gradient(120deg, #fdf0f5 0%, #f0e8f8 100%);
    border-top: 1px dashed #e6d5e8;
">

    <!-- Decorative flower divider -->
    <div style="font-size: 16px; margin-bottom: 8px; opacity: 0.7;">
        🌸 &nbsp; 🌷 &nbsp; 🌸
    </div>

    <!-- Copyright + project credit -->
    <div>
        &copy; <?php echo date('Y'); ?> Niels Brock Lost &amp; Found
        &nbsp;·&nbsp;
        Final Year Project by Asmita Tamang (P2863731)
    </div>

    <!-- Supervisor credit (smaller, below) -->
    <div style="margin-top: 4px; font-size: 12px; opacity: 0.75;">
        Supervised by Dr. Muhammad Waleed
    </div>
</footer>

</body>
</html>