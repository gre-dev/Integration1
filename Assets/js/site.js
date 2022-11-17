function CollapseSidebar() {
    if ($(window).width() > 991) {
        $("body").toggleClass('CollapseSidebar');
    }
    else {
        $("body").toggleClass('showSidebar');
    }
}
function openLogin() {
    $('#LoginModal').modal('show',100)
}