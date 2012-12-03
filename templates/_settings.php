<p>The first setting to decide on is whether to enable encryption. If it is turned on, you can either run
	in test mode (where the original email and IP addresses are preserved) or in real mode, where they are
	deleted. Once you are happy with the system, and are sure it works on your system, real mode is
	recommended.
</p>

<select>
	<option value="off">Off</option>
	<option value="test">Test mode (initially recommended)</option>
	<option value="real">Real mode (be sure before you switch to this)</option>
</select>

<p>You may also set up a CPU load setting. This is set to slow initially, which is recommended
	for shared hosting or if your web site performance would be adversely affected. Remember that if
	you regularly consume a lot of CPU on shared hosts, you may be asked to upgrade to a VPS. If you're
	not sure about this setting, leave it on slow.</p>

<select>
	<option value="slow">Slow searches, low load</option>
	<option value="medium">Faster searches, moderate load</option>
	<option value="fast">Fast searches, high load</option>
</select>