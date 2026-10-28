-- Tax rate to user group mapping
--
-- A rate with no rows here applies to every customer group.
-- A rate with rows applies only to the listed groups
-- (see Tax_type::getRegionRules).

	CREATE PROCEDURE getAll(
		IN tax_rate_id INT,
		OUT fetch_all
	)
	BEGIN
		-- tax_rate_to_user_group
		SELECT *
			FROM tax_rate_to_user_group WHERE 1 = 1

		@IF isset(:tax_rate_id)
		THEN
			AND tax_rate_id = :tax_rate_id
		END @IF
		;
	END

	-- replace all group mappings for one rate

	CREATE PROCEDURE add(
		IN tax_rate_user_group ROWS,
		IN tax_rate_id INT,
		OUT insert_id,
		OUT insert_id
	)
	BEGIN
		-- BEGIN transaction;

		DELETE FROM tax_rate_to_user_group WHERE tax_rate_id = :tax_rate_id;

		-- allow only table fields and set defaults for missing values
		:tax_rate_user_group_data = @FILTER(:tax_rate_user_group, tax_rate_to_user_group)


		@EACH(:tax_rate_user_group_data)
		INSERT INTO tax_rate_to_user_group

			( @KEYS(:each), tax_rate_id )

		VALUES ( :each, :tax_rate_id );

		-- END transaction;

	END

	-- delete mappings for rates

	CREATE PROCEDURE delete(
		IN tax_rate_id ARRAY,
		OUT affected_rows
	)
	BEGIN
		-- tax_rate_to_user_group
		DELETE FROM tax_rate_to_user_group WHERE tax_rate_id IN (:tax_rate_id);
	END
