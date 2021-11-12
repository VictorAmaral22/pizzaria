select sabor.codigo as codigo, sabor.nome as sabor, tipo.nome as tipo, group_concat(ingrediente.nome, ', ') as ingredientes from sabor
    join tipo on sabor.tipo = tipo.codigo
    join saboringrediente on saboringrediente.sabor = sabor.codigo 
    join ingrediente on saboringrediente.ingrediente = ingrediente.codigo
group by sabor.codigo;

select codigo from sabor;

select count(*) as total from (
    select * from sabor
        join tipo on sabor.tipo = tipo.codigo
        join saboringrediente on saboringrediente.sabor = sabor.codigo 
        join ingrediente on saboringrediente.ingrediente = ingrediente.codigo
    group by sabor.codigo);

select sabor.codigo as codigo, sabor.nome as sabor, tipo.nome as tipo, group_concat(ingrediente.nome, ', ') as ingredientes 
from sabor 
	join tipo on sabor.tipo = tipo.codigo 
	join saboringrediente on saboringrediente.sabor = sabor.codigo 
	join ingrediente on saboringrediente.ingrediente = ingrediente.codigo 
where sabor.nome like '%%' 
group by sabor.codigo 

select * from sabor 
join saboringrediente on saboringrediente.sabor = sabor.codigo 
join ingrediente on saboringrediente.ingrediente = ingrediente.codigo 
limit 10;

select count(*) as qtd from ingrediente;

insert into sabor (nome, tipo) values ('NIGERIA', 1);
insert into saboringrediente (sabor, ingrediente) values (1, 30);

select ingrediente.codigo, ingrediente.nome from sabor
    join saboringrediente on saboringrediente.sabor = sabor.codigo
    join ingrediente on saboringrediente.ingrediente = ingrediente.codigo
where sabor.codigo = 1;


delete from saboringrediente where sabor = 1;
update sabor set nome = 'NIGÉRIA', tipo = 2 where codigo = 1;

-- D)
select comanda.numero as comanda, case 
            when strftime('%w', comanda.data) = '0' then 'Dom'
            when strftime('%w', comanda.data) = '1' then 'Seg'
            when strftime('%w', comanda.data) = '2' then 'Ter'
            when strftime('%w', comanda.data) = '3' then 'Qua'
            when strftime('%w', comanda.data) = '4' then 'Qui'
            when strftime('%w', comanda.data) = '5' then 'Sex'
            when strftime('%w', comanda.data) = '6' then 'Sáb'
        end as semana, strftime('%d/%m/%Y', comanda.data) as data, mesa.nome as mesa, tmp1.qtdPizzas as pizzas, tmp2.total as valor, comanda.pago as pago from comanda
    join mesa on comanda.mesa = mesa.codigo
	left join (
		select comanda.numero as comanda, count(*) as qtdPizzas from comanda
			join pizza on comanda.numero = pizza.comanda
		group by comanda) as tmp1 on tmp1.comanda = comanda.numero
	left join (
		select tmp.numero as comanda, sum(tmp.preco) as total from
			(select comanda.numero, pizza.codigo,
				max(case
						when borda.preco is null then 0
						else borda.preco
					end+precoportamanho.preco) as preco
			from comanda
				join pizza on pizza.comanda = comanda.numero
				join pizzasabor on pizzasabor.pizza = pizza.codigo
				join sabor on pizzasabor.sabor = sabor.codigo
				join precoportamanho on precoportamanho.tipo = sabor.tipo and precoportamanho.tamanho = pizza.tamanho
				left join borda on pizza.borda = borda.codigo
			group by comanda.numero, pizza.codigo) as tmp
			join comanda on comanda.numero = tmp.numero
		group by tmp.numero) as tmp2 on tmp2.comanda = comanda.numero;

select numero from comanda
except
select comanda from pizza group by comanda;

select comanda.numero as comanda, group_concat(pizza.codigo, ', ') as pizzas, comanda.pago as pago from comanda
	join pizza on comanda.numero = pizza.comanda
where comanda.pago = 0
group by comanda.numero;

-- E)
select * from mesa;

-- F)
select pizza.codigo, pizza.comanda, tamanho.nome, group_concat(sabor.nome, ', '), borda.nome from pizza 
	join tamanho on pizza.tamanho = tamanho.codigo
	join pizzasabor on pizzasabor.pizza = pizza.codigo 
	join sabor on pizzasabor.sabor = sabor.codigo
	left join borda on pizza.borda = borda.codigo 
group by pizza.codigo
limit 10;

-- G)
select count(*) as qtd from pizza where comanda = "1asd";

select case 
	when pizza.tamanho = "P" then "PEQUENA"
	when pizza.tamanho = "M" then "MÉDIA"
	when pizza.tamanho = "G" then "GRANDE"
	when pizza.tamanho = "F" then "FAMÍLIA"
	end as tamanho, borda.nome as borda, group_concat(sabor.nome, ', ') as sabores, tmp2.preco as preco, tmp3.total as total, tmp.qtd as qtd
from pizza 
	join pizzasabor on pizza.codigo = pizzasabor.pizza
	join sabor on sabor.codigo = pizzasabor.sabor
	join (select pizza.comanda as comanda, count(*) as qtd from pizza group by pizza.comanda) as tmp on tmp.comanda = pizza.comanda
	join (select comanda.numero as comanda, pizza.codigo as pizza,
			max(case
					when borda.preco is null then 0
					else borda.preco
				end+precoportamanho.preco) as preco
		from comanda
			join pizza on pizza.comanda = comanda.numero
			join pizzasabor on pizzasabor.pizza = pizza.codigo
			join sabor on pizzasabor.sabor = sabor.codigo
			join precoportamanho on precoportamanho.tipo = sabor.tipo and precoportamanho.tamanho = pizza.tamanho
			left join borda on pizza.borda = borda.codigo
		group by comanda.numero, pizza.codigo) as tmp2 on tmp2.pizza = pizza.codigo
	join (
		select tmp.numero as comanda, sum(tmp.preco) as total from
			(select comanda.numero, pizza.codigo,
				max(case
						when borda.preco is null then 0
						else borda.preco
					end+precoportamanho.preco) as preco
			from comanda
				join pizza on pizza.comanda = comanda.numero
				join pizzasabor on pizzasabor.pizza = pizza.codigo
				join sabor on pizzasabor.sabor = sabor.codigo
				join precoportamanho on precoportamanho.tipo = sabor.tipo and precoportamanho.tamanho = pizza.tamanho
				left join borda on pizza.borda = borda.codigo
			group by comanda.numero, pizza.codigo) as tmp
			join comanda on comanda.numero = tmp.numero
		group by tmp.numero) as tmp3 on tmp3.comanda = pizza.comanda
	left join borda on pizza.borda = borda.codigo
where pizza.comanda = 1
group by pizza.codigo;
