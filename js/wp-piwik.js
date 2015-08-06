function wp_piwik_datelink(strPage, strDate, intSite) {
	window.location.href = 'index.php?page=' + strPage + '&date=' + strDate
			+ '&wpmu_show_stats=' + intSite;
}