-- Subscription logs

	CREATE PROCEDURE getAll(
		IN subscription_id INT,
		IN start INT,
		IN limit INT,
		OUT fetch_all,
		OUT fetch_one
	)
	BEGIN
		SELECT *
			FROM subscription_log WHERE 1 = 1

		@IF isset(:subscription_id)
		THEN
			AND subscription_log.subscription_id = :subscription_id
		END @IF

		@IF isset(:limit)
		THEN
			LIMIT :limit OFFSET :start
		END @IF;

		SELECT count(*) FROM (

			@SQL_COUNT(subscription_log.subscription_log_id, subscription_log)

		) as count;

	END

	CREATE PROCEDURE get(
		IN subscription_log_id INT,
		OUT fetch_row
	)
	BEGIN
		SELECT *
			FROM subscription_log as _ WHERE subscription_log_id = :subscription_log_id;
	END

	CREATE PROCEDURE add(
		IN subscription_log ROW,
		OUT fetch_one
	)
	BEGIN

		:subscription_log_data = @FILTER(:subscription_log, subscription_log)

		INSERT INTO subscription_log

			( @KEYS(:subscription_log_data) )

		VALUES ( :subscription_log_data ) RETURNING subscription_log_id;

	END

	CREATE PROCEDURE delete(
		IN subscription_log_id ARRAY,
		OUT affected_rows
	)
	BEGIN
		DELETE FROM subscription_log WHERE subscription_log_id IN (:subscription_log_id);
	END
