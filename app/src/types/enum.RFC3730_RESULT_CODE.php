<?php

	/**
	 * EPP responce codes
	 * @category EPP-DRS
	 * @package Modules
	 * @subpackage RegistryModules
	 * @sdk
	 */
	final class RFC3730_RESULT_CODE
	{
		/**
		 * "Command completed successfully"
   		 * This is the usual response code for a successfully completed command
   		 * that is not addressed by any other 1xxx-series response code.
		 *
		 */
		const OK = 1000;

		/**
		 * "Command completed successfully; action pending"
   		 * This response code MUST be returned when responding to a command the
   		 * requires offline activity before the requested action can be
   		 * completed.  See section 2 for a description of other processing
   		 * requirements.
		 *
		 */
		const OK_PENDING = 1001;

		/**
		 * "Command completed successfully; no messages"
   		 * This response code MUST be returned when responding to a <poll>
   		 * request command and the server message queue is empty.
		 *
		 */
		const OK_NO_MESSAGES = 1300;
		
		/**
		 * "Command completed successfully; ack to dequeue"
		 * This response code MUST be returned when responding to a <poll>
   		 * request command and a message has been retrieved from the server
   		 * message queue.
		 *
		 */
		const OK_ACK_DEQUEUE = 1301;
		
		/**
		 * "Command completed successfully; ending session"
   		 * This response code MUST be returned when responding to a successful
   		 * <logout> command.
		 *
		 */
		const OK_END_SESSION = 1500;
		
		/**
		 * "Unknown command"
   		 * This response code MUST be returned when a server receives a command
   		 * element that is not defined by EPP.
		 *
		 */
		const ERR_UNKNOWN_CMD = 2000;
		
		/**
		 * "Command syntax error"
   		 * This response code MUST be returned when a server receives an
   		 * improperly formed command element.
		 *
		 */
		const ERR_CMD_SYNTAX = 2001;
		
		/**
		 * "Command use error"
   		 * This response code MUST be returned when a server receives a properly
   		 * formed command element, but the command can not be executed due to a
   		 * sequencing or context error.  For example, a <logout> command can not
   		 * be executed without having first completed a <login> command.
		 *
		 */
		const ERR_CMD_USE = 2002;
		
		/**
		 * "Required parameter missing"
   		 * This response code MUST be returned when a server receives a command
   		 * for which a required parameter value has not been provided.
		 *
		 */
		const ERR_REQUIED_PARAM_MISS = 2003;
		
		/**
		 * "Parameter value range error"
   		 * This response code MUST be returned when a server receives a command
   		 * parameter whose value is outside the range of values specified by the
   		 * protocol.  The error value SHOULD be returned via a <value> element
   		 *in the EPP response.
		 *
		 */
		const ERR_PARAM_RANGE = 2004;
		
		/**
		 * "Parameter value syntax error"
   		 * This response code MUST be returned when a server receives a command
   		 * containing a parameter whose value is improperly formed.  The error
   		 * value SHOULD be returned via a <value> element in the EPP response.
		 *
		 */
		const ERR_PARAM_SYNTAX = 2005;
		
		/**
		 * "Unimplemented protocol version"
   		 * This response code MUST be returned when a server receives a command
   		 * element specifying a protocol version that is not implemented by the
   		 * server.
		 *
		 */
		const ERR_UNKNOWN_PROTOCOL_VERSION = 2100;
		
		/**
		 * "Unimplemented command"
   		 * This response code MUST be returned when a server receives a valid
   		 * EPP command element that is not implemented by the server.  For
   		 * example, a <transfer> command can be unimplemented for certain object
   		 * types.
		 *
		 */
		const ERR_UNIMPLEMENTED_CMD = 2101;
		
		/**
		 * "Unimplemented option"
   		 * This response code MUST be returned when a server receives a valid
   		 * EPP command element that contains a protocol option that is not
   		 * implemented by the server.
		 *
		 */
		const ERR_UNIMPLEMENTED_OPTION = 2102;
		
		/**
		 * "Unimplemented extension"
   		 * This response code MUST be returned when a server receives a valid
   		 * EPP command element that contains a protocol command extension that
   		 * is not implemented by the server.
		 * 
		 *
		 */
		const ERR_UNIMPLEMENTED_EXT = 2103;
		
		/**
		 * "Billing failure"
   		 * This response code MUST be returned when a server attempts to execute
   		 * a billable operation and the command can not be completed due to a
   		 * client billing failure.
		 *
		 */
		const ERR_BILLING_FAILURE = 2104;
		
		/**
		 * "Object is not eligible for renewal"
 	 	 * This response code MUST be returned when a client attempts to <renew>
   		 * an object that is not eligible for renewal in accordance with server
   		 * policy.
		 *
		 */
		const ERR_NOT_ELIGIBLE_FOR_RENEWAL = 2105;
		
		/**
		 * "Object is not eligible for transfer"
		 *  This response code MUST be returned when a client attempts to
		 *  <transfer> an object that is not eligible for transfer in accordance
		 *  with server policy.
		 *
		 */
		const ERR_NOT_ELIGIBLE_FOR_TRANSFER = 2106;
		
		/**
		 * "Authentication error"
		 * This response code MUST be returned when a server notes an error when
		 * validating client credentials.
		 *
		 */
		const ERR_AUTHENTICATE_ERROR = 2200;
		
		/**
		 * "Authorization error"
   		 * This response code MUST be returned when a server notes a client
   		 * authorization error when executing a command.  This error is used to
   		 * note that a client lacks privileges to execute the requested command.
		 *
		 */
		const ERR_AUTHORIZE_ERROR = 2201;
		
		/**
		 * "Invalid authorization information"
		 * This response code MUST be returned when a server receives invalid
		 * command authorization information required to confirm authorization
		 * to execute a command.  This error is used to note that a client has
		 * the privileges required to execute the requested command, but the
		 * authorization information provided by the client does not match the
		 * authorization information archived by the server.
		 *
		 */
		const ERR_INVALID_AUTHORIZE_DATA = 2202;
		
		/**
		 * 2300    "Object pending transfer"
   		 * This response code MUST be returned when a server receives a command
   		 * to transfer an object that is pending transfer due to an earlier
   		 * transfer request.
		 *
		 */
		const ERR_OBJECT_PENDING_TRANSFER = 2300;
		
		/**
		 * "Object not pending transfer"
   		 * This response code MUST be returned when a server receives a command
   		 * to confirm, reject, or cancel the transfer an object when no command
   		 * has been made to transfer the object.
		 *
		 */
		const ERR_OBJECT_NOT_PENDING_TRANSFER = 2301;
		
		/**
		 * "Object exists"
   		 * This response code MUST be returned when a server receives a command
   		 * to create an object that already exists in the repository.
		 *
		 */
		const ERR_OBJECT_EXISTS = 2302;
		
		/**
		 * "Object does not exist"
		 * This response code MUST be returned when a server receives a command
		 * to query or transform an object that does not exist in the
		 * repository.
		 *
		 */
		const ERR_OBJECT_NOT_EXISTS = 2303;
		
		/**
		 * "Object status prohibits operation"
		 * This response code MUST be returned when a server receives a command
		 * to transform an object that can not be completed due to server policy
		 * or business practices.  For example, a server can disallow <transfer>
		 * commands under terms and conditions that are matters of local policy,
		 * or the server might have received a <delete> command for an object
		 * whose status prohibits deletion.
		 *
		 */
		const ERR_OBJECT_STATUS_PROHIBITS_OP = 2304;
		
		/**
		 * "Object association prohibits operation"
   		 * This response code MUST be returned when a server receives a command
   		 * to transform an object that can not be completed due to dependencies
   		 * on other objects that are associated with the target object.  For
   		 * example, a server can disallow <delete> commands while an object has
   		 * active associations with other objects.
		 *
		 */
		const ERR_OBJECT_ASSOC_PROHIBITS_OP = 2305;
		
		/**
		 * "Parameter value policy error"
   		 * This response code MUST be returned when a server receives a command
   		 * containing a parameter value that is syntactically valid, but
   		 * semantically invalid due to local policy.  For example, the server
   		 * can support a subset of a range of valid protocol parameter values.
   		 * The error value SHOULD be returned via a <value> element in the EPP
   		 * response.
		 *
		 */
		const ERR_PARAM_VALUE_POLICY = 2306;
		
		/**
		 * "Unimplemented object service"
   		 * This response code MUST be returned when a server receives a command
   		 * to operate on an object service that is not supported by the server.
		 *
		 */
		const ERR_UNIMPLEMENTED_OBJECT_SERVICE = 2307;
		
		/**
		 * "Data management policy violation"
		 * This response code MUST be returned when a server receives a command
		 * whose execution results in a violation of server data management
		 * policies.  For example, removing all attribute values or object
		 * associations from an object might be a violation of a server's data
		 * management policies.
		 *
		 */
		const ERR_DATA_POLICY_VIOLATION = 2308;
		
		/**
		 * "Command failed"
		 * This response code MUST be returned when a server is unable to
		 * execute a command due to an internal server error that is not related
		 * to the protocol.  The failure can be transient.  The server MUST keep
		 * any ongoing session active.
		 *
		 */
		const ERR_CMD_FAILED = 2400;
		
		/**
		 * "Command failed; server closing connection"
		 * This response code MUST be returned when a server receives a command
		 * that can not be completed due to an internal server error that is not
		 * related to the protocol.  The failure is not transient, and will
		 * cause other commands to fail as well.  The server MUST end the active
		 * session and close the existing connection.
		 *
		 */
		const ERR_CMD_FAILED_END_SESSION = 2500;
		
		/**
		 * "Authentication error; server closing connection"
		 * This response code MUST be returned when a server notes an error when
		 * validating client credentials and a server-defined limit on the
		 * number of allowable failures has been exceeded.  The server MUST
		 * close the existing connection.
		 *
		 */
		const ERR_AUTH_END_SESSION = 2501;
		
		/**
		 * "Session limit exceeded; server closing connection"
		 * This response code MUST be returned when a server receives a <login>
		 * command, and the command can not be completed because the client has
		 * exceeded a system-defined limit on the number of sessions that the
		 * client can establish.  It might be possible to establish a session by
		 * ending existing unused sessions and closing inactive connections.
		 *
		 */
		const ERR_SESSION_LIMIT_EXCEEDED = 2502;
	}
?>