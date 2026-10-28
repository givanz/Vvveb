-- Subscription logs

	-- get all subscription logs

	CREATE PROCEDURE getAll(
		IN subscription_id INT,
		IN start INT,
		IN limit INT,
		OUT fetch_all,
		OUT fetch_one
	)
	BEGIN
		-- subscription_log
		SELECT subscription_log.*, subscription_status.name AS status_name
			FROM subscription_log
			LEFT JOIN subscription_status ON subscription_status.subscription_status_id = subscription_log.subscription_status_id
				AND subscription_status.language_id = :language_id
			WHERE 1 = 1

			@IF isset(:subscription_id)
			THEN
				AND subscription_log.subscription_id = :subscription_id
			END @IF

			-- limit
			@IF isset(:limit)
			THEN
				LIMIT :limit OFFSET :start
			END @IF;

			SELECT count(*) FROM (

				@SQL_COUNT(subscription_log.subscription_log_id, subscription_log)

			) as count;

	END

	-- get subscription log

	CREATE PROCEDURE get(
		IN subscription_log_id INT,
		OUT fetch_row
	)
	BEGIN
		SELECT _.*, subscription_status.name AS status_name
			FROM subscription_log as _
			LEFT JOIN subscription_status ON subscription_status.subscription_status_id = _.subscription_status_id
				AND subscription_status.language_id = :language_id
			WHERE _.subscription_log_id = :subscription_log_id;
	END

	-- add subscription log

	CREATE PROCEDURE add(
		IN subscription_log ROW,
		OUT insert_id
	)
	BEGIN

		:subscription_log_data = @FILTER(:subscription_log, subscription_log)

		INSERT INTO subscription_log

			( @KEYS(:subscription_log_data) )

		VALUES ( :subscription_log_data );

	END

	-- delete subscription logs

	CREATE PROCEDURE delete(
		IN subscription_log_id ARRAY,
		OUT affected_rows
	)
	BEGIN
		DELETE FROM subscription_log WHERE subscription_log_id IN (:subscription_log_id);
	END
