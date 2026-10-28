<?php

$tools = [
	/*
 * Coding harness tools: file access, shell execution and database queries.
 *
 * These tools expose the local project to AI coding agents (like read/write/
 * edit/bash in coding harnesses). All file paths are sandboxed inside the
 * installation root (configurable via config key mcp.tools.root), database
 * queries are restricted to read-only statements unless config key
 * mcp.tools.db_write is enabled and every call requires MCP admin
 * authentication (see McpBase::auth()).
 */
	[
		'name'        => 'crud',
		'description' => 'CMS data models (post, product, user, order, ... - crud_models lists all). params keys match procedure IN names: add/edit nest the record under the model key with per-language <model>_content lists (flat fields are rejected with an example); edit merges, only supplied fields change; delete takes id arrays (scalars auto-wrapped); get/getAll take flat filters plus search/start/limit/order_by/direction, and columns trims returned fields to just names. add returns the new id plus per-list rows-written counts (content tables have no auto id). language_id/site_id default to 1, slugs auto-generated from names. Reads always allowed. Call crud_models first for exact params.',
		'type'        => 'action',
		'inputSchema' => [
			'type'       => 'object',
			'properties' => [
				'model' => [
					'type'        => 'string',
					'description' => 'Model name, e.g. product, user, post, order, category (basename of admin/sql/mysqli/<model>.sql, CamelCase also accepted)'
				],
				'operation' => [
					'type'        => 'string',
					'enum'        => ['get', 'getAll', 'add', 'edit', 'delete'],
					'description' => 'CRUD operation mapping to a model procedure: get (single record), getAll (list), add (create), edit (update), delete'
				],
				'columns' => [
					'type'        => 'array',
					'items'       => ['type' => 'string'],
					'description' => 'get/getAll only: return just these columns (e.g. ["post_id","name"] skips heavy content bodies); unknown names error with real keys'
				],
				'params' => [
					'type'        => 'object',
					'description' => 'Procedure parameters keyed by IN name: nested record under the model key plus <model>_content lists for add/edit, id arrays for delete, flat scalar filters for get/getAll (see tool description for shapes)'
				]
			],
			'required'             => ['model', 'operation'],
			'additionalProperties' => false
		],
		'outputSchema' => [
			'type'       => 'object',
			'properties' => [
				'model'     => ['type' => 'string'],
				'operation' => ['type' => 'string'],
				'result'    => ['description' => 'Native model procedure result (record, list with count, insert id or affected rows)']
			],
			'required' => ['model', 'operation', 'result']
		],
		'annotations' => [
			'readOnlyHint'    => false,
			'destructiveHint' => true,
			'idempotentHint'  => false,
			'openWorldHint'   => false
		]
	],
	[
		'name'        => 'crud_models',
		'description' => 'Discover available CMS data models for the crud tool. Without arguments lists every model with its procedure names and crud-callability. With a model name returns each procedure with IN parameters (required flags, and field names for ROW/ROWS record params). Convention reminder: crud add/edit nest the record under the first ARRAY parameter (usually the model name) with translations in <model>_content lists - flat field params are rejected, and scalar ids for ARRAY params are auto-wrapped. Read-only.',
		'type'        => 'action',
		'inputSchema' => [
			'type'       => 'object',
			'properties' => [
				'model' => [
					'type'        => 'string',
					'description' => 'Optional model name (e.g. product). Omit to list all models; provide to describe one model\'s procedures and parameters.'
				]
			],
			'additionalProperties' => false
		],
		'outputSchema' => [
			'type'       => 'object',
			'properties' => [
				'models' => [
					'type'        => 'array',
					'description' => 'All models (when model is omitted), each with model, crud-callable operations and all procedure names',
					'items'       => [
						'type'       => 'object',
						'properties' => [
							'model'      => ['type' => 'string'],
							'crud'       => ['type' => 'array', 'items' => ['type' => 'string']],
							'procedures' => ['type' => 'array', 'items' => ['type' => 'string']]
						]
					]
				],
				'count'      => ['type' => 'integer'],
				'model'      => ['type' => 'string', 'description' => 'Described model (when model is provided)'],
				'procedures' => [
					'type'        => 'array',
					'description' => 'Procedures of the model (when model is provided), each with name, crud-callability and IN parameters',
					'items'       => [
						'type'       => 'object',
						'properties' => [
							'name'   => ['type' => 'string'],
							'crud'   => ['type' => 'boolean'],
							'params' => [
								'type'  => 'array',
								'items' => [
									'type'       => 'object',
									'properties' => [
										'name'            => ['type' => 'string'],
										'type'            => ['type' => 'string'],
										'required'        => ['type' => 'boolean', 'description' => 'Must-supply param; omit it only when the call makes sense without it'],
										'fields'          => ['type' => 'array', 'description' => 'Accepted field names for ROW/ROWS record params (table columns minus auto-managed keys)', 'items' => ['type' => 'string']],
										'required_fields' => ['type' => 'array', 'description' => 'Fields with no usable default that omit-fails the INSERT (present only when non-empty)', 'items' => ['type' => 'string']]
									]
								]
							]
						]
					]
				]
			],
			'required' => []
		],
		'annotations' => [
			'readOnlyHint'    => true,
			'destructiveHint' => false,
			'idempotentHint'  => true,
			'openWorldHint'   => false
		]
	],
	[
		'name'        => 'admin',
		'description' => 'Manage CMS subsystems (thin CRUD stubs). Themes: list/get/market/activate/install/delete. Plugins: list/get/market/activate/deactivate/install/uninstall. Cron: list/run/run_all. Cache: clear by scope. Media lives in its own top-level media tool. Reads are always allowed.',
		'type'        => 'action',
		'inputSchema' => [
			'type'       => 'object',
			'properties' => [
				'target' => [
					'type'        => 'string',
					'enum'        => ['themes', 'plugins', 'cron', 'cache'],
					'description' => 'Subsystem to manage: themes, plugins, cron or cache (media has its own top-level tool)'
				],
				'operation' => [
					'type'        => 'string',
					'description' => 'Subsystem operation — themes: list/get/market/activate/install/delete; plugins: list/get/market/activate/deactivate/install/uninstall; cron: list/run/run_all; cache: clear'
				],
				'theme' => [
					'type'        => 'string',
					'description' => 'Theme slug, required for themes get/activate/delete'
				],
				'plugin' => [
					'type'        => 'string',
					'description' => 'Plugin slug, required for plugins get/activate/deactivate/uninstall'
				],
				'category' => [
					'type'        => 'string',
					'description' => 'Category filter for plugins list and market search'
				],
				'site_id' => [
					'type'        => 'integer',
					'description' => 'Site id for themes/plugins, defaults to the current site'
				],
				'job' => [
					'type'        => 'string',
					'description' => 'Cron job name, required for cron run. Use cron list to discover names.'
				],
				'scope' => [
					'type'        => 'string',
					'enum'        => ['all', 'template', 'page', 'database', 'frontend', 'image', 'model'],
					'default'     => 'all',
					'description' => 'Cache scope for cache clear'
				],
				'search' => [
					'type'        => 'string',
					'description' => 'Search keywords for market search'
				],
				'start' => [
					'type'        => 'integer',
					'description' => 'Result offset for market search, defaults to 1'
				],
				'limit' => [
					'type'        => 'integer',
					'description' => 'Result limit for market search (1-50, defaults to 10)'
				],
				'host' => [
					'type'        => 'string',
					'description' => 'Host filter for cache clear with page scope'
				],
			],
			'required'             => ['target', 'operation'],
			'additionalProperties' => false
		],
		'outputSchema' => [
			'type'       => 'object',
			'properties' => [
				'target'    => ['type' => 'string'],
				'operation' => ['type' => 'string'],
				'result'    => ['description' => 'Subsystem result (lists, records, job output or success flags)']
			],
			'required' => ['target', 'operation']
		],
		'annotations' => [
			'readOnlyHint'    => false,
			'destructiveHint' => true,
			'idempotentHint'  => false,
			'openWorldHint'   => false
		]
	],
	[
		'name'        => 'media',
		'description' => 'Manage the media library. list (metadata records, filters/pagination), get (metadata for one file), save (metadata key/values), delete (by file or media_id), upload (base64, 4MB cap), fetch (image URL import, oversized rasters auto-resized), mkdir, rename (newfile path or newname), scan (folder tree: path, levels 1-3, search, paged with 0-based start/limit up to 200). Reads (list/get/scan) are always allowed.',
		'type'        => 'action',
		'inputSchema' => [
			'type'       => 'object',
			'properties' => [
				'operation' => [
					'type'        => 'string',
					'enum'        => ['list', 'get', 'save', 'delete', 'upload', 'fetch', 'mkdir', 'rename', 'scan'],
					'description' => 'Media operation: list/get/save/delete metadata, upload file bytes, fetch image URL, mkdir folder, rename file, scan folder tree'
				],
				'file' => [
					'type'        => 'string',
					'description' => 'Media file path, required for get/save/rename and one way to target delete'
				],
				'content' => [
					'type'        => 'object',
					'description' => 'Metadata key/value pairs, required for save'
				],
				'media_id' => [
					'type'        => 'integer',
					'description' => 'Media record id, alternative to file for delete'
				],
				'file_name' => [
					'type'        => 'string',
					'description' => 'Target file name for upload (required)'
				],
				'data' => [
					'type'        => 'string',
					'description' => 'Base64-encoded file bytes for upload (required, capped at 4MB decoded and the server upload_max_filesize)'
				],
				'url' => [
					'type'        => 'string',
					'description' => 'Public image URL to download for fetch (required, http(s) only, redirects followed, same 4MB cap)'
				],
				'overwrite' => [
					'type'        => 'boolean',
					'description' => 'Replace the existing file for upload/fetch (default false)'
				],
				'path' => [
					'type'        => 'string',
					'description' => 'Media sub-path for upload/mkdir/scan (default root)'
				],
				'folder' => [
					'type'        => 'string',
					'description' => 'New folder name for mkdir (required)'
				],
				'newfile' => [
					'type'        => 'string',
					'description' => 'New media-relative path for rename, alternative to newname'
				],
				'newname' => [
					'type'        => 'string',
					'description' => 'New file name inside the current directory for rename'
				],
				'duplicate' => [
					'type'        => 'boolean',
					'description' => 'Copy instead of renaming for rename'
				],
				'levels' => [
					'type'        => 'integer',
					'description' => 'Depth budget for scan (1-3, default 1)'
				],
				'params' => [
					'type'        => 'object',
					'description' => 'List filters and pagination for list'
				],
				'search' => [
					'type'        => 'string',
					'description' => 'Filename match for scan'
				],
				'start' => [
					'type'        => 'integer',
					'description' => '0-based page offset for scan'
				],
				'limit' => [
					'type'        => 'integer',
					'description' => 'Page size for scan (default 200, max 200)'
				],
			],
			'required'             => ['operation'],
			'additionalProperties' => false
		],
		'outputSchema' => [
			'type'       => 'object',
			'properties' => [
				'operation' => ['type' => 'string'],
				'result'    => ['description' => 'Operation result (records, folder tree page with total, or success flags)']
			],
			'required' => ['operation']
		],
		'annotations' => [
			'readOnlyHint'    => false,
			'destructiveHint' => true,
			'idempotentHint'  => false,
			'openWorldHint'   => false
		]
	],
	[
		'name'        => 'read',
		'description' => 'Read the contents of a file relative to the site root. Supports text files; images and other binaries cannot be read. Output is truncated to 2000 lines or 50KB, whichever comes first.',
		'type'        => 'action',
		'inputSchema' => [
			'type'       => 'object',
			'properties' => [
				'path' => [
					'type'        => 'string',
					'description' => 'Path to the file to read (relative or absolute, must be inside the site root)'
				],
				'offset' => [
					'type'        => 'integer',
					'description' => 'Line number to start reading from (1-indexed)',
					'default'     => 1
				],
				'limit' => [
					'type'        => 'integer',
					'description' => 'Maximum number of lines to read',
					'default'     => 2000
				]
			],
			'required'             => ['path'],
			'additionalProperties' => false
		],
		'outputSchema' => [
			'type'       => 'object',
			'properties' => [
				'content' => [
					'type'        => 'string',
					'description' => 'File contents with line numbers'
				],
				'totalLines' => [
					'type' => 'integer'
				]
			],
			'required' => ['content']
		],
		'annotations' => [
			'readOnlyHint'    => true,
			'destructiveHint' => false,
			'idempotentHint'  => true,
			'openWorldHint'   => false
		]
	],
	[
		'name'        => 'write',
		'description' => 'Write content to a file relative to the site root. Creates the file if it does not exist, overwrites it otherwise. Parent directories are created automatically.',
		'type'        => 'action',
		'inputSchema' => [
			'type'       => 'object',
			'properties' => [
				'path' => [
					'type'        => 'string',
					'description' => 'Path to the file to write (relative or absolute, must be inside the site root)'
				],
				'content' => [
					'type'        => 'string',
					'description' => 'Content to write to the file'
				]
			],
			'required'             => ['path', 'content'],
			'additionalProperties' => false
		],
		'outputSchema' => [
			'type'       => 'object',
			'properties' => [
				'success' => ['type' => 'boolean'],
				'bytes'   => ['type' => 'integer'],
				'created' => ['type' => 'boolean', 'description' => 'True when the file did not exist before']
			],
			'required' => ['success']
		],
		'annotations' => [
			'readOnlyHint'    => false,
			'destructiveHint' => true,
			'idempotentHint'  => false,
			'openWorldHint'   => false
		]
	],
	[
		'name'        => 'bash',
		'description' => 'Execute a bash command inside the site root directory and return stdout, stderr and exit code. Use for shell operations like ls, find, grep on files, running build tools etc.',
		'type'        => 'action',
		'inputSchema' => [
			'type'       => 'object',
			'properties' => [
				'command' => [
					'type'        => 'string',
					'description' => 'The bash command line to execute'
				],
				'timeout' => [
					'type'        => 'integer',
					'description' => 'Timeout in seconds (1-120, default 30)',
					'default'     => 30
				],
				'cwd' => [
					'type'        => 'string',
					'description' => 'Working directory for the command (must be inside the site root, defaults to site root)'
				]
			],
			'required'             => ['command'],
			'additionalProperties' => false
		],
		'outputSchema' => [
			'type'       => 'object',
			'properties' => [
				'stdout'   => ['type' => 'string'],
				'stderr'   => ['type' => 'string'],
				'exitCode' => ['type' => 'integer'],
				'timedOut' => ['type' => 'boolean']
			],
			'required' => ['stdout', 'exitCode']
		],
		'annotations' => [
			'readOnlyHint'    => false,
			'destructiveHint' => true,
			'idempotentHint'  => false,
			'openWorldHint'   => true
		]
	],
	[
		'name'        => 'bash_sub',
		'description' => 'Pure-PHP sandboxed bash fallback when native shell functions are disabled. No system calls; filesystem virtualised to the site root. Supports pipes, redirects, variables and control flow.',
		'type'        => 'action',
		'inputSchema' => [
			'type'       => 'object',
			'properties' => [
				'command' => [
					'type'        => 'string',
					'description' => 'The bash command line to execute (pure PHP interpreter)'
				],
				'timeout' => [
					'type'        => 'integer',
					'description' => 'Timeout in seconds (1-120, default 30) — enforced via execution limits in bashbox',
					'default'     => 30
				],
				'cwd' => [
					'type'        => 'string',
					'description' => 'Working directory for the command (must be inside the site root, defaults to site root)'
				]
			],
			'required'             => ['command'],
			'additionalProperties' => false
		],
		'outputSchema' => [
			'type'       => 'object',
			'properties' => [
				'stdout'    => ['type' => 'string'],
				'stderr'    => ['type' => 'string'],
				'exitCode'  => ['type' => 'integer'],
				'timedOut'  => ['type' => 'boolean'],
				'engine'    => ['type' => 'string', 'description' => 'Execution engine used (bashbox)'],
				'truncated' => ['type' => 'boolean']
			],
			'required' => ['stdout', 'exitCode']
		],
		'annotations' => [
			'readOnlyHint'    => false,
			'destructiveHint' => true,
			'idempotentHint'  => false,
			'openWorldHint'   => false
		]
	],
	[
		'name'        => 'glob',
		'description' => 'Find files by matching a glob pattern (e.g. "plugins/*/plugin.php", "**/*.tpl") relative to the site root. Returns up to 500 matching paths sorted alphabetically.',
		'type'        => 'action',
		'inputSchema' => [
			'type'       => 'object',
			'properties' => [
				'pattern' => [
					'type'        => 'string',
					'description' => 'Glob pattern with optional wildcards (*, ?, [abc]) relative to the site root'
				],
				'limit' => [
					'type'        => 'integer',
					'description' => 'Maximum number of results (default 500)',
					'default'     => 500
				]
			],
			'required'             => ['pattern'],
			'additionalProperties' => false
		],
		'outputSchema' => [
			'type'       => 'object',
			'properties' => [
				'matches' => [
					'type'  => 'array',
					'items' => ['type' => 'string']
				],
				'count' => ['type' => 'integer']
			],
			'required' => ['matches']
		],
		'annotations' => [
			'readOnlyHint'    => true,
			'destructiveHint' => false,
			'idempotentHint'  => true,
			'openWorldHint'   => false
		]
	],
	[
		'name'        => 'grep',
		'description' => 'Search file contents recursively with a regular expression. Returns matching lines with file, line number and text, skipping binary files and directories like .git/node_modules.',
		'type'        => 'action',
		'inputSchema' => [
			'type'       => 'object',
			'properties' => [
				'pattern' => [
					'type'        => 'string',
					'description' => 'PCRE regular expression to search for'
				],
				'path' => [
					'type'        => 'string',
					'description' => 'Directory or file to search in (default: site root)',
					'default'     => '.'
				],
				'include' => [
					'type'        => 'string',
					'description' => 'Only search files matching this glob pattern, e.g. *.php',
					'default'     => '*'
				],
				'ignoreCase' => [
					'type'        => 'boolean',
					'description' => 'Case insensitive search',
					'default'     => false
				],
				'maxResults' => [
					'type'        => 'integer',
					'description' => 'Maximum number of matches returned (default 200)',
					'default'     => 200
				]
			],
			'required'             => ['pattern'],
			'additionalProperties' => false
		],
		'outputSchema' => [
			'type'       => 'object',
			'properties' => [
				'matches' => [
					'type'  => 'array',
					'items' => [
						'type'       => 'object',
						'properties' => [
							'file' => ['type' => 'string'],
							'line' => ['type' => 'integer'],
							'text' => ['type' => 'string']
						]
					]
				],
				'count'     => ['type' => 'integer'],
				'truncated' => ['type' => 'boolean']
			],
			'required' => ['matches']
		],
		'annotations' => [
			'readOnlyHint'    => true,
			'destructiveHint' => false,
			'idempotentHint'  => true,
			'openWorldHint'   => false
		]
	],
	[
		'name'        => 'database_query',
		'description' => 'Run a SQL query against the site database. Positional ? placeholders are bound to the values passed in params. Read-only statements (SELECT/SHOW/DESCRIBE/EXPLAIN/WITH) are always allowed. SELECT queries without a LIMIT get one appended automatically (max 1000 rows).',
		'type'        => 'action',
		'inputSchema' => [
			'type'       => 'object',
			'properties' => [
				'query' => [
					'type'        => 'string',
					'description' => 'SQL query to execute, use ? placeholders for bound parameters'
				],
				'params' => [
					'type'        => 'array',
					'description' => 'Optional positional parameter values for ? placeholders',
					// permissive item schema (any JSON value): an empty PHP
					// array would serialize to JSON [] which draft 2020-12
					// rejects ($dynamicRef requires object|boolean).
					'items' => [
						'description' => 'A single bound parameter value (string, number, boolean or null)'
					]
				],
				'maxRows' => [
					'type'        => 'integer',
					'description' => 'Row cap applied to SELECT queries without LIMIT (default 1000)',
					'default'     => 1000
				]
			],
			'required'             => ['query'],
			'additionalProperties' => false
		],
		'outputSchema' => [
			'type'       => 'object',
			'properties' => [
				'rows' => [
					'type'        => 'array',
					'description' => 'Result rows for SELECT statements'
				],
				'rowCount'     => ['type' => 'integer'],
				'affectedRows' => ['type' => 'integer', 'description' => 'Affected rows for write statements'],
				'insertId'     => ['type' => 'integer', 'description' => 'Insert id for INSERT statements'],
				'truncated'    => ['type' => 'boolean']
			]
		],
		'annotations' => [
			'readOnlyHint'    => true,
			'destructiveHint' => false,
			'idempotentHint'  => true,
			'openWorldHint'   => false
		]
	],
	[
		'name'        => 'str_replace_editor',
		'description' => 'Custom editing tool for viewing, creating and editing files. State is persistent across calls. Supports commands: view / create / edit. If path is a file, view displays the result of applying cat -n; if path is a directory, view lists non-hidden files up to 2 levels deep. create cannot overwrite existing files. edit performs multiple exact replacements via edits[].oldText/newText (multi-edit only).',
		'type'        => 'action',
		'inputSchema' => [
			'type'       => 'object',
			'properties' => [
				'command' => [
					'type'        => 'string',
					'enum'        => ['view', 'create', 'edit'],
					'description' => 'The command to run: view (read file or list directory), create (create new file), edit (multiple disjoint exact replacements via edits[])'
				],
				'path' => [
					'type'        => 'string',
					'description' => 'Path to the file or directory (relative or absolute, must be inside the site root). For view, may be a directory.'
				],
				'file_text' => [
					'type'        => 'string',
					'description' => 'Required for command create: content to write to the new file'
				],
				'view_range' => [
					'type'        => 'array',
					'items'       => ['type' => 'integer'],
					'description' => 'Optional for command view: [start_line, end_line] range (1-indexed, -1 for end). E.g. [1, 20]'
				],
				'edits' => [
					'type'        => 'array',
					'description' => 'Required for command edit: array of {oldText, newText} — each oldText must match a unique non-overlapping region (delegates to CodingAgent\\Tools multi-edit engine)',
					'items'       => [
						'type'       => 'object',
						'required'   => ['oldText', 'newText'],
						'properties' => [
							'oldText' => ['type' => 'string', 'description' => 'Exact text to replace, must be unique in file'],
							'newText' => ['type' => 'string', 'description' => 'Replacement text']
						],
						'additionalProperties' => false
					]
				],
			],
			'required'             => ['command', 'path'],
			'additionalProperties' => false
		],
		'outputSchema' => [
			'type'       => 'object',
			'properties' => [
				'output'  => ['type' => 'string', 'description' => 'Formatted file view content or success message with optional diff'],
				'success' => ['type' => 'boolean']
			]
		],
		'annotations' => [
			'readOnlyHint'    => false,
			'destructiveHint' => true,
			'idempotentHint'  => false,
			'openWorldHint'   => false
		]
	]
];

//Windows-only: expose a native PowerShell tool when running on Windows
//(powershell.exe on Windows PowerShell 5.1, pwsh.exe on PowerShell 7+)
//where bash (/bin/bash) is typically unavailable.
if (PHP_OS_FAMILY === 'Windows' || DIRECTORY_SEPARATOR === '\\') {
	$tools[] = [
		'name'        => 'powershell',
		'description' => 'Execute a PowerShell command inside the site root directory and return stdout, stderr and exit code. Windows-only alternative to bash (which needs /bin/bash). Use for shell operations like dir, Get-ChildItem, Select-String, running build tools etc.',
		'type'        => 'action',
		'inputSchema' => [
			'type'       => 'object',
			'properties' => [
				'command' => [
					'type'        => 'string',
					'description' => 'The PowerShell command line to execute'
				],
				'timeout' => [
					'type'        => 'integer',
					'description' => 'Timeout in seconds (1-120, default 30)',
					'default'     => 30
				],
				'cwd' => [
					'type'        => 'string',
					'description' => 'Working directory for the command (must be inside the site root, defaults to site root)'
				]
			],
			'required'             => ['command'],
			'additionalProperties' => false
		],
		'outputSchema' => [
			'type'       => 'object',
			'properties' => [
				'stdout'   => ['type' => 'string'],
				'stderr'   => ['type' => 'string'],
				'exitCode' => ['type' => 'integer'],
				'timedOut' => ['type' => 'boolean'],
				'engine'   => ['type' => 'string', 'description' => 'PowerShell executable used (pwsh or powershell)']
			],
			'required' => ['stdout', 'exitCode']
		],
		'annotations' => [
			'readOnlyHint'    => false,
			'destructiveHint' => true,
			'idempotentHint'  => false,
			'openWorldHint'   => true
		]
	];
}

return $tools;
