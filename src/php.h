/* This macro definition has no actual functionality and is used solely to suppress IDE warnings or error messages. */
#define uint32_t unsigned int
#define uint64_t unsigned long
#define zend_long long long
#define __SYMTABLE_CACHE_SIZE__ 32
#define __ZEND_ARRAY_SIZE__ 64
#define __PHP85_EG_FEILDS__
#define size_t int
/* --- END ---*/


typedef struct _IO_FILE FILE;
typedef unsigned long sigset_t;

typedef union epoll_data
{
	void *ptr;
	int fd;
	uint32_t u32;
	uint64_t u64;
} epoll_data_t;

typedef struct epoll_event
{
	uint32_t events;   /* Epoll events */
	epoll_data_t data; /* User data variable */
} epoll_event;

typedef struct _zend_resource {
	uint32_t gc[2];	//zend_refcounted_h
	zend_long         handle; // TODO: may be removed ???
	int               type;
	void             *ptr;
} zend_resource;
typedef struct
{
	zend_resource    *res;
	uint32_t type_info;
	uint32_t num_args; /* arguments number for EX(This) */
} zval;

typedef struct _zend_execute_data zend_execute_data;

struct _zend_execute_data
{
	const void *opline;		 /* zend_op                */
	zend_execute_data *call; /* current call                   */
	zval *return_value;
	void *func; /* zend_function              */
	zval This;
	zend_execute_data *prev_execute_data;
	void *symbol_table;//zend_array
	void **run_time_cache; /* cache op_array->run_time_cache */
	void *extra_named_params;//zend_array
};

typedef struct
{
	zval uninitialized_zval;
	zval error_zval;
	void *symtable_cache[__SYMTABLE_CACHE_SIZE__];//zend_array
	void **symtable_cache_limit;//zend_array
	void **symtable_cache_ptr;//zend_array
	char symbol_table[__ZEND_ARRAY_SIZE__];//zend_array
	char included_files[__ZEND_ARRAY_SIZE__];//zend_array
	void *bailout; // JMP_BUF
	int error_reporting;
	__PHP85_EG_FEILDS__
	int exit_status;

	void *function_table; //zend_array
	void *class_table;//zend_array
	void *zend_constants;//zend_array

	zval *vm_stack_top;
	zval *vm_stack_end;
	void* vm_stack;//zend_vm_stack, typedef struct _zend_vm_stack *zend_vm_stack;
	size_t vm_stack_page_size;

	void *current_execute_data;
	void *fake_scope;
	/* Other member fields are omitted .... */
	/* ....... */
} zend_executor_globals;

typedef struct _php_stream  {
	const void *ops;
	void *abstract;
} php_stream;
typedef struct {
	FILE *file;
	int fd;
} php_stdio_stream_data;
typedef struct {
	int php_sock;
} php_netstream_data;

int epoll_create(int size);
int epoll_create1(int __flags);
int epoll_ctl(int epfd, int op, int fd, struct epoll_event *event);
int epoll_wait(int epfd, struct epoll_event *events, int maxevents, int timeout);
int epoll_pwait(int epfd, struct epoll_event *events, int maxevents, int timeout, const sigset_t *sigmask);

int errno;

char *strerror(int errno);
int fileno(void *stream);

