<!-- Alerts -->
<?php
    if (count($result['errors'])) {
        $msg_str = '<li>' . implode('<li>', $result['errors']);
?>
        <div class="alert alert-danger" role="alert">
            <ul>
                <?php echo $msg_str; ?>
            </ul>
        </div>
<?php
    }
?>

<?php
if (count($result['warnings'])) {
    $msg_str = '<li>' . implode('<li>', $result['warnings']);
    ?>
    <div class="alert alert-warning" role="alert">
        <ul>
            <?php echo $msg_str; ?>
        </ul>
    </div>
    <?php
}
?>

<?php
    if (count($result['success'])) {
        $msg_str = '<li>' . implode('<li>', $result['success']);
?>
        <div class="alert alert-success" role="alert">
            <ul>
                <?php echo $msg_str; ?>
            </ul>
        </div>
<?php
    }
?>
