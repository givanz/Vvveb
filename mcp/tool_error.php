<?php

declare(strict_types=1);

/*
Routine MCP tool failure.

Throw McpToolError (instead of RuntimeException) for expected caller-side
failures: missing files, bad arguments, unknown models/themes/plugins,
sandbox escapes, disabled-write gates, caps. These return clean isError
results WITHOUT hitting the server error log, so an agent's early
trial-and-error exploration doesn't flood it.

Genuine problems (IO failures, DB outages, broken environments, coding
bugs) keep throwing RuntimeException/Error and are still logged with
traces under DEBUG in tools_call().
*/

class McpToolError extends RuntimeException {}
