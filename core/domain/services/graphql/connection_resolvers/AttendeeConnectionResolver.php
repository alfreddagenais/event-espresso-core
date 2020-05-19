<?php

namespace EventEspresso\core\domain\services\graphql\connection_resolvers;

use EE_Error;
use EEM_Attendee;
use EEM_Ticket;
use EventEspresso\core\exceptions\InvalidDataTypeException;
use EventEspresso\core\exceptions\InvalidInterfaceException;
use InvalidArgumentException;
use ReflectionException;
use Throwable;

/**
 * Class DatetimeConnectionResolver
 */
class AttendeeConnectionResolver extends AbstractConnectionResolver
{
    // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function get_loader_name()
    {
        return 'espresso_attendee';
    }

    /**
     * @return EEM_Attendee
     * @throws EE_Error
     * @throws InvalidArgumentException
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     */
    // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function get_query()
    {
        return EEM_Attendee::instance();
    }


    /**
     * Return an array of item IDs from the query
     *
     * @return array
     */
    // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function get_ids()
    {
        $results = $this->query->get_col($this->query_args);

        return ! empty($results) ? $results : [];
    }


    /**
     * Determine whether the Query should execute. If it's determined that the query should
     * not be run based on context such as, but not limited to, who the user is, where in the
     * ResolveTree the Query is, the relation to the node the Query is connected to, etc
     * Return false to prevent the query from executing.
     *
     * @return bool
     */
    // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function should_execute()
    {
        if (false === $this->should_execute) {
            return false;
        }

        return $this->should_execute;
    }


    /**
     * Here, we map the args from the input, then we make sure that we're only querying
     * for IDs. The IDs are then passed down the resolve tree, and deferred resolvers
     * handle batch resolution of the posts.
     *
     * @return array
     * @throws EE_Error
     * @throws InvalidArgumentException
     * @throws ReflectionException
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     */
    // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function get_query_args()
    {
        $where_params = [];
        $query_args   = [];

        $query_args['limit'] = $this->getLimit();

        // Avoid multiple entries by join.
        $query_args['group_by'] = 'ATT_ID';

        $query_args['default_where_conditions'] = 'minimum';

        /**
         * Collect the input_fields and sanitize them to prepare them for sending to the Query
         */
        $input_fields = [];
        if (! empty($this->args['where'])) {
            $input_fields = $this->sanitizeInputFields($this->args['where']);

            // Since we do not have any falsy values in query params
            // Lets get rid of empty values
            $input_fields = array_filter($input_fields);

            // Use the proper operator.
            if (! empty($input_fields['Registration.Event.EVT_ID']) && is_array($input_fields['Registration.Event.EVT_ID'])) {
                $input_fields['Registration.Event.EVT_ID'] = ['IN', $input_fields['Registration.Event.EVT_ID']];
            }
            if (! empty($input_fields['Registration.Ticket.TKT_ID']) && is_array($input_fields['Registration.Ticket.TKT_ID'])) {
                $input_fields['Registration.Ticket.TKT_ID'] = ['IN', $input_fields['Registration.Ticket.TKT_ID']];
            }
            // If Ticket param is passed, it will have preference over Datetime param
            // So, use Datetime param only if a Ticket param is not passed
            if (! empty($input_fields['Datetime.DTT_ID']) && empty($input_fields['Registration.Ticket.TKT_ID'])) {
                $datetimeIds = $input_fields['Datetime.DTT_ID'];
                // Make sure it's an array, ready for "IN" operator
                $datetimeIds = is_array($datetimeIds) ? $datetimeIds : [$datetimeIds];

                try {
                    // Get related ticket IDs for the given dates
                    $ticketIds = EEM_Ticket::instance()->get_col([
                        [
                            'Datetime.DTT_ID' => ['IN', $datetimeIds],
                            'TKT_deleted'     => ['IN', [true, false]],
                        ],
                        'default_where_conditions' => 'minimum',
                    ]);
                } catch (Throwable $th) {
                    $ticketIds = [];
                }

                if (!empty($ticketIds)) {
                    $input_fields['Registration.Ticket.TKT_ID'] = ['IN', $ticketIds];
                }
            }
            // Since there is no relation between Attendee and Datetime, we need to remove it
            unset($input_fields['Datetime.DTT_ID']);
        }

        /**
         * Merge the input_fields with the default query_args
         */
        if (! empty($input_fields)) {
            $where_params = array_merge($where_params, $input_fields);
        }

        list($query_args, $where_params) = $this->mapOrderbyInputArgs($query_args, $where_params, 'ATT_ID');

        $query_args[] = $where_params;

        /**
         * Return the $query_args
         */
        return $query_args;
    }


    /**
     * This sets up the "allowed" args, and translates the GraphQL-friendly keys to model
     * friendly keys.
     *
     * @param array $where_args
     * @return array
     */
    public function sanitizeInputFields(array $where_args)
    {
        $arg_mapping = [
            // There is no direct relation between Attendee and Datetime
            // But we will handle it via Tickets related to given dates
            'datetime'      => 'Datetime.DTT_ID',
            'datetimeIn'    => 'Datetime.DTT_ID',
            'event'         => 'Registration.Event.EVT_ID',
            'eventIn'       => 'Registration.Event.EVT_ID',
            'regTicket'     => 'Registration.Ticket.TKT_ID',
            'regTicketIn'   => 'Registration.Ticket.TKT_ID',
            'regTicketIdIn' => 'Registration.Ticket.TKT_ID',
            'regTicketId'   => 'Registration.Ticket.TKT_ID', // priority.
            'regStatus'     => 'Registration.Status.STS_ID',
        ];
        return $this->sanitizeWhereArgsForInputFields(
            $where_args,
            $arg_mapping,
            ['datetime', 'datetimeIn', 'event', 'eventIn', 'regTicket', 'regTicketIn']
        );
    }
}
