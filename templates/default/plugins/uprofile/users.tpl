<div class="block-title">{{ lang.uprofile['profile_of'] }} {{ user.name }} {% if (user.flags.isOwnProfile) %}[
		<a href="/profile.html">{{ lang.uprofile['edit_profile'] }}</a> ]{% endif %}</div>
<div class="block-user-info">
	<div class="avatar">
		<img src="{{ user.avatar }}" alt=""/>
		{% if not (global.user.status == 0) %}
			{% if pluginIsActive('pm') %}
				<a href="/plugin/pm/?action=write&name={{ user.name }}">{{ lang.uprofile['write_pm'] }}</a>{% endif %}
		{% endif %}
	</div>
	<div class="avatar">
		<img src="{{ user.photo_thumb }}" alt=""/>
		{% if (user.flags.hasPhoto) %}
			<a href="{{ user.photo }}" target="_blank">{{ lang.uprofile['zoom_photo'] }}</a>{% endif %}
	</div>
	<div class="user-info">
		<table class="table" cellspacing="0" cellpadding="0">
			<tr>
				<td>{{ lang.uprofile['user'] }}:</td>
				<td class="second">{{ user.name }} [id: {{ user.id }}]</td>
			</tr>
			<tr>
				<td>{{ lang.uprofile['status'] }}:</td>
				<td class="second">{{ user.status }}</td>
			</tr>
			<tr>
				<td>{{ lang.uprofile['regdate'] }}:</td>
				<td class="second">{{ user.reg }}</td>
			</tr>
			<tr>
				<td>{{ lang.uprofile['last'] }}:</td>
				<td class="second">{{ user.last }}</td>
			</tr>
		</table>
	</div>
</div>
<div class="block-title-mini">{{ lang.uprofile['activity_data'] }}</div>
<p>
	{{ lang.uprofile['all_news'] }}: {{ user.news }}<br>
	{{ lang.uprofile['all_comments'] }}: {{ user.comments }}
</p>