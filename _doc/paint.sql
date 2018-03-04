update periodic set color = 'cyan';

update periodic set color = 'yellow' where ([group] >= '13') and ([group] != '');
update periodic set color = 'yellow' where [group] >= '3' and (period = '2' or period = '3');

update periodic set color = 'magenta' where [group] <= '2' or period = 1;

update periodic set color = 'green' where [number] >= '57' and [number] <= '71';
update periodic set color = 'green' where [number] >= '89' and [number] <= '103';

